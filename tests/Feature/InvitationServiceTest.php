<?php

use App\Enums\InvitationChannel;
use App\Enums\PositionStatus;
use App\Exceptions\ActiveInvitationExistsException;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\InvitationToken;
use App\Models\Position;
use App\Notifications\InvitationNotification;
use App\Services\InvitationService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->service = app(InvitationService::class);
});

it('creates a token with a UUID and 7-day expiration', function () {
    $agent = Agent::factory()->create();
    $campaign = Campaign::factory()->create();

    $token = $this->service->createToken($agent, $campaign);

    expect($token)->toBeInstanceOf(InvitationToken::class);
    expect($token->agent_id)->toBe($agent->id);
    expect($token->campaign_id)->toBe($campaign->id);
    expect($token->token)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
    expect($token->expires_at->diffInDays(now(), true))->toBeGreaterThan(6.9);
    expect($token->expires_at->diffInDays(now(), true))->toBeLessThan(7.1);
    expect($token->revoked_at)->toBeNull();
    expect($token->used_at)->toBeNull();
});

it('refuses to create a second active token for the same agent/campaign', function () {
    $agent = Agent::factory()->create();
    $campaign = Campaign::factory()->create();

    $this->service->createToken($agent, $campaign);

    expect(fn () => $this->service->createToken($agent, $campaign))
        ->toThrow(ActiveInvitationExistsException::class);
});

it('createOrReplaceToken revokes the active token and creates a new one', function () {
    $agent = Agent::factory()->create();
    $campaign = Campaign::factory()->create();

    $old = $this->service->createToken($agent, $campaign);
    $new = $this->service->createOrReplaceToken($agent, $campaign);

    expect($new->id)->not->toBe($old->id);
    expect($new->token)->not->toBe($old->token);

    $old->refresh();
    expect($old->revoked_at)->not->toBeNull();
    expect($new->revoked_at)->toBeNull();
});

it('sends an email notification when agent has an email', function () {
    Notification::fake();

    $agent = Agent::factory()->create(['email' => 'agent@example.com']);
    $campaign = Campaign::factory()->create();
    $token = $this->service->createToken($agent, $campaign);

    $result = $this->service->sendInvitation($token);

    expect($result)->toBeNull();
    Notification::assertSentTo($agent, InvitationNotification::class);

    $token->refresh();
    expect($token->notification_channel)->toBe(InvitationChannel::Email);
    expect($token->notification_sent_at)->not->toBeNull();
});

it('builds an invitation email from the campaign and open positions', function () {
    $agent = Agent::factory()->create([
        'email' => 'agent@example.com',
        'first_name' => 'Awa',
        'last_name' => 'DIOP',
    ]);
    $campaign = Campaign::factory()->create(['title' => 'Recrutement DSI 2026']);
    Position::factory()->create([
        'campaign_id' => $campaign->id,
        'title' => 'Ingénieur DevOps',
    ]);
    Position::factory()->create([
        'campaign_id' => $campaign->id,
        'title' => 'Poste fermé',
        'status' => PositionStatus::Closed,
    ]);
    $token = $this->service->createToken($agent, $campaign);

    $mail = (new InvitationNotification($token))->toMail($agent);

    expect($mail->introLines)->toContain('Vous êtes invité(e) à soumettre un dossier de candidature pour la campagne : **Recrutement DSI 2026**.');
    expect($mail->introLines)->toContain('Postes ouverts : Ingénieur DevOps.');
    expect(implode(' ', $mail->introLines))->not->toContain('Poste fermé');
});

it('returns a manual message and sends no notification when agent has no email', function () {
    Notification::fake();

    $agent = Agent::factory()->create([
        'email' => null,
        'first_name' => 'Fatou',
        'last_name' => 'NDIAYE',
    ]);
    $campaign = Campaign::factory()->create(['title' => 'Recrutement DSI 2026']);
    Position::factory()->create([
        'campaign_id' => $campaign->id,
        'title' => 'Ingénieur DevOps',
    ]);
    $token = $this->service->createToken($agent, $campaign);

    $message = $this->service->sendInvitation($token);

    expect($message)->toBeString();
    expect($message)->toContain('Fatou');
    expect($message)->toContain('NDIAYE');
    expect($message)->toContain('Recrutement DSI 2026');
    expect($message)->toContain($token->token);
    Notification::assertNothingSent();

    $token->refresh();
    expect($token->notification_channel)->toBe(InvitationChannel::Manual);
    expect($token->notification_sent_at)->not->toBeNull();
});

it('sends email invitations in batch only to eligible agents', function () {
    Notification::fake();

    $campaign = Campaign::factory()->create();
    $eligibleAgent = Agent::factory()->create(['email' => 'eligible@example.com']);
    $secondEligibleAgent = Agent::factory()->create(['email' => 'second@example.com']);
    $agentWithoutEmail = Agent::factory()->create(['email' => null]);
    $alreadyInvitedAgent = Agent::factory()->create(['email' => 'already@example.com']);
    $existingToken = $this->service->createToken($alreadyInvitedAgent, $campaign);

    $result = $this->service->sendEmailBatch([
        $eligibleAgent,
        $secondEligibleAgent,
        $agentWithoutEmail,
        $alreadyInvitedAgent,
    ], $campaign);

    expect($result->sent)->toBe(2);
    expect($result->skippedNoEmail)->toBe(1);
    expect($result->skippedActiveInvitation)->toBe(1);
    expect($result->failed)->toBe(0);
    expect($result->total())->toBe(4);

    Notification::assertSentTo($eligibleAgent, InvitationNotification::class);
    Notification::assertSentTo($secondEligibleAgent, InvitationNotification::class);
    Notification::assertNotSentTo($agentWithoutEmail, InvitationNotification::class);
    Notification::assertNotSentTo($alreadyInvitedAgent, InvitationNotification::class);

    $sentTokens = InvitationToken::query()
        ->where('campaign_id', $campaign->id)
        ->whereIn('agent_id', [$eligibleAgent->id, $secondEligibleAgent->id])
        ->get();

    expect($sentTokens)->toHaveCount(2);
    expect($sentTokens->pluck('notification_channel')->all())->each->toBe(InvitationChannel::Email);
    expect($sentTokens->pluck('notification_sent_at')->all())->each->not->toBeNull();

    $existingToken->refresh();
    expect($existingToken->revoked_at)->toBeNull();
    expect(InvitationToken::query()
        ->where('campaign_id', $campaign->id)
        ->where('agent_id', $alreadyInvitedAgent->id)
        ->count())->toBe(1);
});
