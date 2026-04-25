<?php

use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Position;
use App\Models\Submission;
use App\Models\User;
use App\Services\InvitationService;

it('renders the main admin recruitment pages for an authenticated user', function () {
    $admin = User::factory()->create();
    $agent = Agent::factory()->create();
    $campaign = Campaign::factory()->create();
    $position = Position::factory()->create(['campaign_id' => $campaign->id]);
    $token = app(InvitationService::class)->createToken($agent, $campaign);
    $submission = Submission::factory()->create([
        'agent_id' => $agent->id,
        'position_id' => $position->id,
        'invitation_token_id' => $token->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.agents.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.campaigns.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.submissions.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.submissions.view', ['record' => $submission]))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invitation-tokens.index'))
        ->assertOk();
});
