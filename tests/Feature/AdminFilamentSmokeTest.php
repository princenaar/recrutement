<?php

use App\Enums\CampaignFormType;
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
        ->get(route('filament.admin.pages.dashboard'))
        ->assertOk();

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

it('disambiguates imported region from questionnaire region choices on submission details', function () {
    $admin = User::factory()->create();
    $agent = Agent::factory()->create([
        'category' => 'Informatique',
        'current_position' => 'Licence',
        'region' => 'Kédougou',
    ]);
    $criteriaCampaign = Campaign::factory()->create([
        'form_type' => CampaignFormType::CriteriaQuestionnaire,
    ]);
    $criteriaPosition = Position::factory()->create(['campaign_id' => $criteriaCampaign->id]);
    $criteriaToken = app(InvitationService::class)->createToken($agent, $criteriaCampaign);
    $criteriaSubmission = Submission::factory()->create([
        'agent_id' => $agent->id,
        'position_id' => $criteriaPosition->id,
        'invitation_token_id' => $criteriaToken->id,
        'region_choices' => ['Dakar', 'Thiès', 'Saint-Louis'],
        'responses' => ['currently_active' => 'no'],
        'cv_path' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.submissions.view', ['record' => $criteriaSubmission]))
        ->assertOk()
        ->assertSee('Région initiale importée')
        ->assertSee('Kédougou')
        ->assertSee('Diplôme')
        ->assertSee('Informatique')
        ->assertSee('Niveau diplôme')
        ->assertSee('Licence')
        ->assertSee('Régions choisies')
        ->assertSee('Dakar')
        ->assertSee('Thiès')
        ->assertSee('Saint-Louis')
        ->assertDontSee('Thi\\u00e8s')
        ->assertDontSee('Catégorie')
        ->assertDontSee('Fonction actuelle');

    $documentCampaign = Campaign::factory()->create([
        'form_type' => CampaignFormType::DocumentDossier,
    ]);
    $documentPosition = Position::factory()->create(['campaign_id' => $documentCampaign->id]);
    $documentAgent = Agent::factory()->create([
        'category' => 'Ingénieur en informatique',
        'current_position' => 'Analyste',
    ]);
    $documentToken = app(InvitationService::class)->createToken($documentAgent, $documentCampaign);
    $documentSubmission = Submission::factory()->create([
        'agent_id' => $documentAgent->id,
        'position_id' => $documentPosition->id,
        'invitation_token_id' => $documentToken->id,
        'region_choices' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.submissions.view', ['record' => $documentSubmission]))
        ->assertOk()
        ->assertSee('Catégorie')
        ->assertSee('Ingénieur en informatique')
        ->assertSee('Fonction actuelle')
        ->assertSee('Analyste')
        ->assertDontSee('Régions choisies');
});
