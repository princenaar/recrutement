<?php

use App\Enums\CampaignFormType;
use App\Filament\Exports\AgentExporter;
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
        'score_breakdown' => [
            'degree' => 15,
            'experience' => 2,
            'snis' => 0,
            'dhis2' => 0,
            'computer_skills' => 5,
            'terrain_motivation' => 2,
        ],
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
        ->assertSee('Détail des points')
        ->assertSee('Diplôme : 15 pt')
        ->assertSee('Expérience : 2 pt')
        ->assertSee('Connaissance SNIS : 0 pt')
        ->assertSee('Connaissance DHIS2 : 0 pt')
        ->assertSee('Maîtrise informatique : 5 pt')
        ->assertSee('Motivation terrain : 2 pt')
        ->assertDontSee('0 : 15 pt')
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

it('defines selectable agent export columns including related recruitment details', function () {
    $columns = collect(AgentExporter::getColumns());
    $columnNames = $columns
        ->map(fn ($column): string => $column->getName())
        ->all();

    expect($columnNames)->toContain(
        'matricule',
        'first_name',
        'last_name',
        'category',
        'current_position',
        'invitation_tokens_count',
        'active_invitations_count',
        'invitations_detail',
        'submissions_count',
        'submission_positions',
        'submission_statuses',
        'region_choices',
        'submissions_detail',
    );

    $invitationsDetail = $columns->first(fn ($column): bool => $column->getName() === 'invitations_detail');
    $submissionsDetail = $columns->first(fn ($column): bool => $column->getName() === 'submissions_detail');

    expect($invitationsDetail->getLabel())
        ->toBe('Détail invitations')
        ->and($submissionsDetail->getLabel())
        ->toBe('Détail candidatures')
        ->and($invitationsDetail->isEnabledByDefault())
        ->toBeFalse()
        ->and($submissionsDetail->isEnabledByDefault())
        ->toBeFalse();
});
