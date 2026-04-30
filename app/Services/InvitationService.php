<?php

namespace App\Services;

use App\Enums\InvitationChannel;
use App\Enums\PositionStatus;
use App\Exceptions\ActiveInvitationExistsException;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\InvitationToken;
use App\Notifications\InvitationNotification;
use App\Support\InvitationBatchResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class InvitationService
{
    public function createToken(Agent $agent, Campaign $campaign): InvitationToken
    {
        $existing = InvitationToken::active()
            ->where('agent_id', $agent->id)
            ->where('campaign_id', $campaign->id)
            ->first();

        if ($existing !== null) {
            throw new ActiveInvitationExistsException($existing);
        }

        return InvitationToken::create([
            'agent_id' => $agent->id,
            'campaign_id' => $campaign->id,
            'token' => (string) Str::uuid(),
            'expires_at' => now()->addDays(
                config('recrutement.invitation_token_validity_days')
            ),
        ]);
    }

    public function createOrReplaceToken(Agent $agent, Campaign $campaign): InvitationToken
    {
        return DB::transaction(function () use ($agent, $campaign) {
            InvitationToken::active()
                ->where('agent_id', $agent->id)
                ->where('campaign_id', $campaign->id)
                ->update(['revoked_at' => now()]);

            return InvitationToken::create([
                'agent_id' => $agent->id,
                'campaign_id' => $campaign->id,
                'token' => (string) Str::uuid(),
                'expires_at' => now()->addDays(
                    config('recrutement.invitation_token_validity_days')
                ),
            ]);
        });
    }

    /**
     * Dispatch the invitation to the agent.
     *
     * @return string|null null if an email was sent, or the manual message text
     *                     to be copy-pasted (SMS/WhatsApp) if the agent has no email.
     */
    public function sendInvitation(InvitationToken $token): ?string
    {
        $token->loadMissing(['agent', 'campaign']);

        if ($token->agent->email !== null) {
            $this->sendEmailInvitation($token);

            return null;
        }

        $message = $this->buildManualMessage($token);

        $token->update([
            'notification_channel' => InvitationChannel::Manual,
            'notification_sent_at' => now(),
        ]);

        return $message;
    }

    public function sendEmailInvitation(InvitationToken $token): void
    {
        $token->loadMissing(['agent', 'campaign']);

        Notification::send($token->agent, new InvitationNotification($token));

        $token->update([
            'notification_channel' => InvitationChannel::Email,
            'notification_sent_at' => now(),
        ]);
    }

    /**
     * @param  iterable<Agent>  $agents
     */
    public function sendEmailBatch(iterable $agents, Campaign $campaign): InvitationBatchResult
    {
        $sent = 0;
        $skippedNoEmail = 0;
        $skippedActiveInvitation = 0;
        $failed = 0;

        foreach ($agents as $agent) {
            if (blank($agent->email)) {
                $skippedNoEmail++;

                continue;
            }

            $hasActiveInvitation = InvitationToken::active()
                ->where('agent_id', $agent->id)
                ->where('campaign_id', $campaign->id)
                ->exists();

            if ($hasActiveInvitation) {
                $skippedActiveInvitation++;

                continue;
            }

            try {
                $token = $this->createToken($agent, $campaign);
                $this->sendEmailInvitation($token);
                $sent++;
            } catch (ActiveInvitationExistsException) {
                $skippedActiveInvitation++;
            } catch (Throwable $exception) {
                report($exception);

                $failed++;
            }
        }

        return new InvitationBatchResult(
            sent: $sent,
            skippedNoEmail: $skippedNoEmail,
            skippedActiveInvitation: $skippedActiveInvitation,
            failed: $failed,
        );
    }

    public function buildManualMessage(InvitationToken $token): string
    {
        $token->loadMissing(['agent', 'campaign.positions']);
        $url = route('candidate.portal', ['token' => $token->token]);

        $openPositions = $token->campaign->positions
            ->where('status', PositionStatus::Open)
            ->pluck('title')
            ->all();

        $positionsBlock = $openPositions === []
            ? ''
            : "\n\nPostes ouverts : ".implode(' · ', $openPositions).'.';

        return <<<TEXT
        Bonjour {$token->agent->first_name} {$token->agent->last_name},

        Vous êtes invité(e) à candidater dans le cadre de la campagne publique « {$token->campaign->title} » du Ministère de la Santé et de l'Hygiène Publique.{$positionsBlock}

        Accédez à votre dossier (lien personnel) : {$url}

        Ce lien est valide jusqu'au {$token->expires_at->format('d/m/Y à H:i')}.

        — Équipe de recrutement MSHP
        TEXT;
    }
}
