<?php

use App\Enums\CampaignFormType;
use App\Enums\SubmissionStatus;
use App\Filament\Exports\AgentExporter;
use App\Filament\Exports\SubmissionExporter;
use App\Filament\Resources\Agents\Pages\ListAgents;
use App\Filament\Resources\InvitationTokens\Pages\ListInvitationTokens;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\InvitationToken;
use App\Models\Position;
use App\Models\Submission;
use App\Models\User;
use App\Services\InvitationService;
use Filament\Facades\Filament;
use Livewire\Livewire;

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

it('enables database notifications for export download links', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('filament.admin.pages.dashboard'))
        ->assertOk();

    expect(Filament::getCurrentPanel()->hasDatabaseNotifications())->toBeTrue();
});

it('filters imported candidates by submitted application presence', function () {
    $admin = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $position = Position::factory()->create(['campaign_id' => $campaign->id]);

    $withoutSubmission = Agent::factory()->create([
        'matricule' => 'IMP-A-001',
        'import_name' => 'Import A',
    ]);
    $withSubmittedSubmission = Agent::factory()->create([
        'matricule' => 'IMP-A-002',
        'import_name' => 'Import A',
    ]);
    $withDraftSubmission = Agent::factory()->create([
        'matricule' => 'IMP-A-003',
        'import_name' => 'Import A',
    ]);
    $otherImportWithoutSubmission = Agent::factory()->create([
        'matricule' => 'IMP-B-001',
        'import_name' => 'Import B',
    ]);

    $submittedToken = InvitationToken::factory()->create([
        'agent_id' => $withSubmittedSubmission->id,
        'campaign_id' => $campaign->id,
    ]);
    $draftToken = InvitationToken::factory()->create([
        'agent_id' => $withDraftSubmission->id,
        'campaign_id' => $campaign->id,
    ]);

    Submission::factory()->create([
        'agent_id' => $withSubmittedSubmission->id,
        'position_id' => $position->id,
        'invitation_token_id' => $submittedToken->id,
        'submitted_at' => now(),
        'status' => SubmissionStatus::Submitted,
    ]);
    Submission::factory()->create([
        'agent_id' => $withDraftSubmission->id,
        'position_id' => $position->id,
        'invitation_token_id' => $draftToken->id,
        'submitted_at' => null,
        'status' => SubmissionStatus::Draft,
    ]);

    Livewire::actingAs($admin)
        ->test(ListAgents::class)
        ->filterTable('import_name', 'Import A')
        ->filterTable('submitted_submission', 'without')
        ->assertCountTableRecords(2)
        ->assertCanSeeTableRecords([
            $withoutSubmission,
            $withDraftSubmission,
        ])
        ->assertCanNotSeeTableRecords([
            $withSubmittedSubmission,
            $otherImportWithoutSubmission,
        ])
        ->filterTable('submitted_submission', 'with')
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([
            $withSubmittedSubmission,
        ])
        ->assertCanNotSeeTableRecords([
            $withoutSubmission,
            $withDraftSubmission,
            $otherImportWithoutSubmission,
        ]);
});

it('filters candidates by active invitation presence', function () {
    $admin = User::factory()->create();
    $campaign = Campaign::factory()->create();

    $withActiveInvitation = Agent::factory()->create(['matricule' => 'INV-ACTIVE']);
    $withExpiredInvitation = Agent::factory()->create(['matricule' => 'INV-EXPIRED']);
    $withRevokedInvitation = Agent::factory()->create(['matricule' => 'INV-REVOKED']);
    $withoutInvitation = Agent::factory()->create(['matricule' => 'INV-NONE']);

    InvitationToken::factory()->create([
        'agent_id' => $withActiveInvitation->id,
        'campaign_id' => $campaign->id,
        'expires_at' => now()->addDay(),
        'revoked_at' => null,
    ]);
    InvitationToken::factory()->create([
        'agent_id' => $withExpiredInvitation->id,
        'campaign_id' => $campaign->id,
        'expires_at' => now()->subDay(),
        'revoked_at' => null,
    ]);
    InvitationToken::factory()->create([
        'agent_id' => $withRevokedInvitation->id,
        'campaign_id' => $campaign->id,
        'expires_at' => now()->addDay(),
        'revoked_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(ListAgents::class)
        ->filterTable('active_invitation', 'with')
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([
            $withActiveInvitation,
        ])
        ->assertCanNotSeeTableRecords([
            $withExpiredInvitation,
            $withRevokedInvitation,
            $withoutInvitation,
        ])
        ->filterTable('active_invitation', 'without')
        ->assertCountTableRecords(3)
        ->assertCanSeeTableRecords([
            $withExpiredInvitation,
            $withRevokedInvitation,
            $withoutInvitation,
        ])
        ->assertCanNotSeeTableRecords([
            $withActiveInvitation,
        ]);
});

it('shows the whatsapp invitation action only for active invitations with a valid senegalese phone number', function () {
    $admin = User::factory()->create();
    $campaign = Campaign::factory()->create();

    $activeValidToken = InvitationToken::factory()->create([
        'agent_id' => Agent::factory()->create(['phone' => '+221 77 535 62 89'])->id,
        'campaign_id' => $campaign->id,
        'expires_at' => now()->addDay(),
        'revoked_at' => null,
    ]);
    $activeInvalidToken = InvitationToken::factory()->create([
        'agent_id' => Agent::factory()->create(['phone' => '331775356289'])->id,
        'campaign_id' => $campaign->id,
        'expires_at' => now()->addDay(),
        'revoked_at' => null,
    ]);
    $expiredValidToken = InvitationToken::factory()->create([
        'agent_id' => Agent::factory()->create(['phone' => '775356289'])->id,
        'campaign_id' => $campaign->id,
        'expires_at' => now()->subDay(),
        'revoked_at' => null,
    ]);
    $revokedValidToken = InvitationToken::factory()->create([
        'agent_id' => Agent::factory()->create(['phone' => '00221775356289'])->id,
        'campaign_id' => $campaign->id,
        'expires_at' => now()->addDay(),
        'revoked_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(ListInvitationTokens::class)
        ->assertTableActionVisible('sendWhatsapp', $activeValidToken)
        ->assertTableActionHidden('sendWhatsapp', $activeInvalidToken)
        ->assertTableActionHidden('sendWhatsapp', $expiredValidToken)
        ->assertTableActionHidden('sendWhatsapp', $revokedValidToken);
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

it('defines selectable submission export columns with excel friendly json fields', function () {
    $columns = collect(SubmissionExporter::getColumns());
    $columnNames = $columns
        ->map(fn ($column): string => $column->getName())
        ->all();

    expect($columnNames)->toContain(
        'agent.matricule',
        'agent.full_name',
        'position.title',
        'position.campaign.title',
        'diplomas_detail',
        'responses',
        'responses.currently_active',
        'region_choices',
        'score_breakdown',
        'invitation_status',
    );

    $responses = $columns->first(fn ($column): bool => $column->getName() === 'responses');
    $scoreBreakdown = $columns->first(fn ($column): bool => $column->getName() === 'score_breakdown');
    $diplomasDetail = $columns->first(fn ($column): bool => $column->getName() === 'diplomas_detail');

    expect($responses->getLabel())
        ->toBe('Réponses questionnaire')
        ->and($responses->isEnabledByDefault())
        ->toBeFalse()
        ->and($scoreBreakdown->getLabel())
        ->toBe('Détail des points')
        ->and($scoreBreakdown->isEnabledByDefault())
        ->toBeFalse()
        ->and($diplomasDetail->getLabel())
        ->toBe('Détail diplômes')
        ->and($diplomasDetail->isEnabledByDefault())
        ->toBeFalse();
});
