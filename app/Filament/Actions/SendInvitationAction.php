<?php

namespace App\Filament\Actions;

use App\Enums\CampaignStatus;
use App\Enums\InvitationChannel;
use App\Models\Agent;
use App\Models\Campaign;
use App\Services\InvitationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class SendInvitationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'sendInvitation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Inviter')
            ->icon('heroicon-o-paper-airplane')
            ->modalHeading('Inviter ce candidat à candidater')
            ->modalSubmitActionLabel('Envoyer l\'invitation')
            ->schema([
                Select::make('campaign_id')
                    ->label('Campagne')
                    ->helperText('Le candidat choisira lui-même le poste parmi ceux de la campagne.')
                    ->options(fn () => Campaign::query()
                        ->where('status', CampaignStatus::Active->value)
                        ->orderByDesc('id')
                        ->pluck('title', 'id')
                        ->all())
                    ->required(),
            ])
            ->action(function (array $data, Agent $record): void {
                $campaign = Campaign::findOrFail($data['campaign_id']);

                $service = app(InvitationService::class);
                $token = $service->createOrReplaceToken($record, $campaign);
                $message = $service->sendInvitation($token);

                if ($token->fresh()->notification_channel === InvitationChannel::Manual) {
                    Notification::make()
                        ->title('Invitation préparée — envoi manuel requis')
                        ->body($message ?? '')
                        ->warning()
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Invitation envoyée par email')
                    ->success()
                    ->send();
            });
    }
}
