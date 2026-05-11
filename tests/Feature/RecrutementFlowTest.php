<?php

use App\Enums\CampaignFormType;
use App\Enums\CampaignStatus;
use App\Enums\InvitationChannel;
use App\Enums\PositionStatus;
use App\Enums\SubmissionStatus;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Diploma;
use App\Models\Position;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Services\AgentImportService;
use App\Services\InvitationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    Notification::fake();
    Storage::fake(config('recrutement.storage_disk'));
});

function recruitmentFlowPdf(string $name = 'document.pdf', int $kilobytes = 100): UploadedFile
{
    return UploadedFile::fake()->create($name, $kilobytes, 'application/pdf');
}

/**
 * @return array{campaign: Campaign, open: Position, second_open: Position, closed: Position}
 */
function recruitmentFlowCampaign(CampaignFormType $formType = CampaignFormType::DocumentDossier): array
{
    $campaign = Campaign::create([
        'title' => 'Recrutement DSI 2026',
        'description' => 'Campagne de test',
        'status' => CampaignStatus::Active,
        'form_type' => $formType,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);

    return [
        'campaign' => $campaign,
        'open' => Position::create([
            'campaign_id' => $campaign->id,
            'title' => 'Ingénieur DevOps',
            'status' => PositionStatus::Open,
        ]),
        'second_open' => Position::create([
            'campaign_id' => $campaign->id,
            'title' => 'Chef de projet SI',
            'status' => PositionStatus::Open,
        ]),
        'closed' => Position::create([
            'campaign_id' => $campaign->id,
            'title' => 'Poste fermé',
            'status' => PositionStatus::Closed,
        ]),
    ];
}

function recruitmentFlowInvite(Agent $agent, Campaign $campaign): InvitationChannel
{
    $token = app(InvitationService::class)->createToken($agent, $campaign);
    $message = app(InvitationService::class)->sendInvitation($token);

    $token->refresh();

    if ($agent->email === null) {
        expect($message)->toBeString()
            ->and($message)->toContain($agent->first_name)
            ->and($message)->toContain($campaign->title)
            ->and($message)->toContain($token->token);

        return $token->notification_channel;
    }

    expect($message)->toBeNull();
    Notification::assertSentTo($agent, InvitationNotification::class);

    return $token->notification_channel;
}

/**
 * @return array{submission: Submission, diploma: Diploma}
 */
function recruitmentFlowSubmitDocument(object $test, string $token, Position $position, array $overrides = []): array
{
    $payload = array_replace_recursive([
        'position_id' => $position->id,
        'current_structure' => 'Hôpital Le Dantec',
        'current_service' => 'DSI',
        'service_entry_date' => now()->subYears(6)->format('Y-m-d'),
        'motivation_note' => 'Très intéressé par ce poste.',
        'cv' => recruitmentFlowPdf('cv.pdf'),
        'new_diplomas' => [
            [
                'title' => 'Master MIAGE',
                'institution' => 'UCAD',
                'year' => 2018,
                'file' => recruitmentFlowPdf('diplome.pdf'),
            ],
        ],
    ], $overrides);

    $test->post(route('candidate.save', ['token' => $token]), $payload)
        ->assertRedirect();

    $submission = Submission::query()
        ->where('position_id', $position->id)
        ->latest('id')
        ->firstOrFail();

    return [
        'submission' => $submission,
        'diploma' => $submission->diplomas()->firstOrFail(),
    ];
}

function recruitmentFlowSubmitCriteria(object $test, string $token, Position $position, array $overrides = []): Submission
{
    $payload = array_replace([
        'position_id' => $position->id,
        'currently_active' => 'no',
        'degree_level' => 'licence_data_health',
        'experience_years' => 2,
        'knows_snis' => 'yes',
        'dhis2_level' => 'basic',
        'computer_skills' => 'yes',
        'region_choices' => ['Dakar', 'Thiès'],
        'motivation_note' => 'Disponible pour le terrain.',
    ], $overrides);

    $test->post(route('candidate.save', ['token' => $token]), $payload)
        ->assertRedirect();

    return Submission::query()->latest('id')->firstOrFail();
}

function recruitmentFlowImportWpFormsAgent(): Agent
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        [
            'Nom',
            'Date de naissance',
            'Lieu de naissance',
            'Téléphone',
            'Email',
            'Diplôme',
            'Niveau Diplôme',
            'Filiére/Domaine',
            'Année diplôme',
            "Numéro d'identification CNI ou PASSEPORT",
            'Lieu de résidence',
            'Piéce jointe diplôme',
            'Curriculum vitae',
            'Choix région affectation',
        ],
        [
            'Aminata FALL',
            '1994-10-03',
            'Louga',
            '771234567',
            'aminata@example.test',
            'Informatique',
            'Licence',
            'Gestion des données',
            '2020',
            '1234567890123',
            'Rufisque, Dakar',
            'https://example.test/diplome.pdf',
            'https://example.test/cv.pdf',
            'Kédougou',
        ],
    ]);

    $path = tempnam(sys_get_temp_dir(), 'wpforms-recruitment-flow-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    $result = app(AgentImportService::class)->import($path, 'WPForms gestionnaires 2026');
    @unlink($path);

    expect($result->created)->toBe(1);

    return Agent::where('matricule', '1234567890123')->firstOrFail();
}

it('covers the iHRIS document dossier flow from import to shortlist', function () {
    $importResult = app(AgentImportService::class)->import(base_path('tests/fixtures/agents_sample.xlsx'), 'iHRIS avril 2026');
    expect($importResult->created)->toBe(5);

    ['campaign' => $campaign, 'open' => $position, 'second_open' => $secondPosition, 'closed' => $closedPosition] = recruitmentFlowCampaign();

    $agent = Agent::query()->whereNotNull('email')->firstOrFail();
    $channel = recruitmentFlowInvite($agent, $campaign);
    expect($channel)->toBe(InvitationChannel::Email);

    $token = $agent->invitationTokens()->where('campaign_id', $campaign->id)->firstOrFail();

    $this->get(route('candidate.portal', ['token' => $token->token]))
        ->assertOk()
        ->assertViewIs('candidate.portal')
        ->assertSee($agent->matricule)
        ->assertSee($agent->first_name)
        ->assertSee($position->title)
        ->assertSee($secondPosition->title)
        ->assertDontSee($closedPosition->title);

    ['submission' => $submission, 'diploma' => $diploma] = recruitmentFlowSubmitDocument($this, $token->token, $position, [
        'first_name' => 'Hacker',
        'matricule' => 'HACKED',
        'agent_id' => 999,
    ]);

    $agent->refresh();
    expect($agent->matricule)->not->toBe('HACKED')
        ->and($agent->first_name)->not->toBe('Hacker')
        ->and($submission->agent_id)->toBe($agent->id)
        ->and($submission->status)->toBe(SubmissionStatus::Submitted)
        ->and($submission->current_structure)->toBe('Hôpital Le Dantec')
        ->and($submission->seniority_years)->toBe(6);

    $disk = Storage::disk(config('recrutement.storage_disk'));
    $disk->assertExists("submissions/{$token->token}/cv.pdf");
    $disk->assertExists($diploma->file_path);

    $firstSubmittedAt = $submission->submitted_at;
    $this->travel(2)->minutes();

    $this->post(route('candidate.save', ['token' => $token->token]), [
        'position_id' => $secondPosition->id,
        'current_structure' => 'Hôpital Principal',
        'current_service' => 'DSI',
        'motivation_note' => 'Mise à jour.',
    ])->assertRedirect();

    $submission->refresh();
    expect($submission->position_id)->toBe($position->id)
        ->and($submission->submitted_at->equalTo($firstSubmittedAt))->toBeTrue()
        ->and($submission->last_updated_at->greaterThan($firstSubmittedAt))->toBeTrue()
        ->and($submission->current_structure)->toBe('Hôpital Principal');

    $this->get(route('admin.files.cv', ['submission' => $submission]))
        ->assertRedirect(route('filament.admin.auth.login'));

    $admin = User::factory()->create();
    $this->actingAs($admin)
        ->get(route('admin.files.cv', ['submission' => $submission]))
        ->assertOk()
        ->assertDownload("cv_{$agent->matricule}_{$submission->id}.pdf");

    $this->actingAs($admin)
        ->get(route('admin.files.diploma', ['diploma' => $diploma]))
        ->assertOk()
        ->assertDownload();

    $submission->update([
        'status' => SubmissionStatus::Shortlisted,
        'shortlisted_at' => now(),
        'shortlisted_by' => $admin->id,
    ]);

    expect($submission->fresh()->status)->toBe(SubmissionStatus::Shortlisted)
        ->and($submission->fresh()->shortlisted_by)->toBe($admin->id);
});

it('covers the iHRIS manual invitation document flow through rejection', function () {
    app(AgentImportService::class)->import(base_path('tests/fixtures/agents_sample.xlsx'), 'iHRIS avril 2026');
    ['campaign' => $campaign, 'open' => $position] = recruitmentFlowCampaign();

    $agent = Agent::query()->whereNull('email')->firstOrFail();
    $channel = recruitmentFlowInvite($agent, $campaign);
    expect($channel)->toBe(InvitationChannel::Manual);
    Notification::assertNotSentTo($agent, InvitationNotification::class);

    $token = $agent->invitationTokens()->where('campaign_id', $campaign->id)->firstOrFail();
    ['submission' => $submission] = recruitmentFlowSubmitDocument($this, $token->token, $position);

    $submission->update([
        'status' => SubmissionStatus::Rejected,
        'rejection_note' => 'Profil non aligné avec les exigences du poste.',
    ]);

    expect($submission->fresh()->status)->toBe(SubmissionStatus::Rejected)
        ->and($submission->fresh()->rejection_note)->toBe('Profil non aligné avec les exigences du poste.');
});

it('covers the WPForms criteria questionnaire flow from import to shortlist', function () {
    $agent = recruitmentFlowImportWpFormsAgent();
    expect($agent->import_source)->toBe('wpforms_gestionnaire_donnees')
        ->and($agent->import_name)->toBe('WPForms gestionnaires 2026')
        ->and($agent->source_payload['Curriculum vitae'])->toBe('https://example.test/cv.pdf');

    ['campaign' => $campaign, 'open' => $position] = recruitmentFlowCampaign(CampaignFormType::CriteriaQuestionnaire);
    $channel = recruitmentFlowInvite($agent, $campaign);
    expect($channel)->toBe(InvitationChannel::Email);

    $token = $agent->invitationTokens()->where('campaign_id', $campaign->id)->firstOrFail();

    $this->get(route('candidate.portal', ['token' => $token->token]))
        ->assertOk()
        ->assertViewIs('candidate.criteria-portal')
        ->assertSee('Êtes-vous actuellement en activité ?', false)
        ->assertDontSee('Curriculum vitae');

    $submission = recruitmentFlowSubmitCriteria($this, $token->token, $position, [
        'region_choices' => ['Dakar', 'Kédougou'],
        'degree_level' => 'master_data_health',
        'experience_years' => 5,
        'dhis2_level' => 'advanced',
    ]);

    expect($submission->agent_id)->toBe($agent->id)
        ->and($submission->cv_path)->toBeNull()
        ->and($submission->region_choices)->toBe(['Dakar', 'Kédougou'])
        ->and($submission->score_breakdown['terrain_motivation'])->toBe(5)
        ->and($submission->raw_score)->toBe(65)
        ->and((float) $submission->normalized_score)->toBe(100.0);

    $admin = User::factory()->create();
    $this->actingAs($admin)
        ->get(route('admin.files.cv', ['submission' => $submission]))
        ->assertNotFound();

    $submission->update([
        'status' => SubmissionStatus::Shortlisted,
        'shortlisted_at' => now(),
        'shortlisted_by' => $admin->id,
    ]);

    expect($submission->fresh()->status)->toBe(SubmissionStatus::Shortlisted);
});

it('scores criteria questionnaire candidates differently when active or inactive', function () {
    $agent = Agent::factory()->create(['structure' => 'Structure iHRIS']);
    ['campaign' => $campaign, 'open' => $position] = recruitmentFlowCampaign(CampaignFormType::CriteriaQuestionnaire);

    $inactiveToken = app(InvitationService::class)->createToken($agent, $campaign);
    $inactiveSubmission = recruitmentFlowSubmitCriteria($this, $inactiveToken->token, $position);

    expect($inactiveSubmission->responses['currently_active'])->toBe('no')
        ->and($inactiveSubmission->responses['activity_location'])->toBeNull()
        ->and($inactiveSubmission->current_structure)->toBe('Structure iHRIS')
        ->and($inactiveSubmission->raw_score)->toBe(43)
        ->and((float) $inactiveSubmission->normalized_score)->toBe(66.15);

    $activeAgent = Agent::factory()->create();
    $activeToken = app(InvitationService::class)->createToken($activeAgent, $campaign);
    $activeSubmission = recruitmentFlowSubmitCriteria($this, $activeToken->token, $position, [
        'currently_active' => 'yes',
        'activity_location' => 'District sanitaire de Koumpentoum',
        'degree_level' => 'master_data_health',
        'experience_years' => 5,
        'dhis2_level' => 'advanced',
        'region_choices' => ['Kédougou'],
    ]);

    expect($activeSubmission->responses['currently_active'])->toBe('yes')
        ->and($activeSubmission->responses['activity_location'])->toBe('District sanitaire de Koumpentoum')
        ->and($activeSubmission->current_structure)->toBe('District sanitaire de Koumpentoum')
        ->and($activeSubmission->raw_score)->toBe(65)
        ->and((float) $activeSubmission->normalized_score)->toBe(100.0);
});

it('blocks invalid, expired and revoked tokens on GET and POST', function () {
    ['campaign' => $campaign, 'open' => $position] = recruitmentFlowCampaign();
    $agent = Agent::factory()->create();

    $this->get(route('candidate.portal', ['token' => 'unknown-token']))
        ->assertNotFound();
    $this->post(route('candidate.save', ['token' => 'unknown-token']), [])
        ->assertNotFound();

    $expiredToken = app(InvitationService::class)->createToken($agent, $campaign);
    $expiredToken->update(['expires_at' => now()->subMinute()]);

    $this->get(route('candidate.portal', ['token' => $expiredToken->token]))
        ->assertOk()
        ->assertViewIs('candidate.expired');
    $this->post(route('candidate.save', ['token' => $expiredToken->token]), [
        'position_id' => $position->id,
        'current_structure' => 'Hôpital',
        'current_service' => 'Service',
        'cv' => recruitmentFlowPdf('cv.pdf'),
        'new_diplomas' => [
            [
                'title' => 'Licence',
                'file' => recruitmentFlowPdf('licence.pdf'),
            ],
        ],
    ])->assertOk()->assertViewIs('candidate.expired');

    $revokedToken = app(InvitationService::class)->createToken(Agent::factory()->create(), $campaign);
    $revokedToken->update(['revoked_at' => now()]);

    $this->get(route('candidate.portal', ['token' => $revokedToken->token]))
        ->assertOk()
        ->assertViewIs('candidate.error');
    $this->post(route('candidate.save', ['token' => $revokedToken->token]), [])
        ->assertOk()
        ->assertViewIs('candidate.error');

    expect(Submission::count())->toBe(0);
});

it('rejects closed and foreign positions before a document submission is created', function () {
    ['campaign' => $campaign, 'closed' => $closedPosition] = recruitmentFlowCampaign();
    $token = app(InvitationService::class)->createToken(Agent::factory()->create(), $campaign);

    $foreignPosition = Position::factory()->create([
        'campaign_id' => Campaign::factory()->create()->id,
        'status' => PositionStatus::Open,
    ]);

    foreach ([$closedPosition, $foreignPosition] as $invalidPosition) {
        $this->post(route('candidate.save', ['token' => $token->token]), [
            'position_id' => $invalidPosition->id,
            'current_structure' => 'Hôpital',
            'current_service' => 'Service',
            'cv' => recruitmentFlowPdf('cv.pdf'),
            'new_diplomas' => [
                [
                    'title' => 'Licence',
                    'file' => recruitmentFlowPdf('licence.pdf'),
                ],
            ],
        ])->assertSessionHasErrors('position_id');
    }

    expect(Submission::count())->toBe(0);
});

it('does not allow a candidate to delete a diploma from another submission', function () {
    ['campaign' => $campaign, 'open' => $position] = recruitmentFlowCampaign();
    $token = app(InvitationService::class)->createToken(Agent::factory()->create(), $campaign);

    $otherToken = app(InvitationService::class)->createToken(Agent::factory()->create(), $campaign);
    ['submission' => $otherSubmission, 'diploma' => $otherDiploma] = recruitmentFlowSubmitDocument($this, $otherToken->token, $position);

    $this->post(route('candidate.save', ['token' => $token->token]), [
        'position_id' => $position->id,
        'current_structure' => 'Hôpital Principal',
        'current_service' => 'Service',
        'cv' => recruitmentFlowPdf('cv.pdf'),
        'diplomas_to_delete' => [$otherDiploma->id],
        'new_diplomas' => [
            [
                'title' => 'Licence',
                'file' => recruitmentFlowPdf('licence.pdf'),
            ],
        ],
    ])->assertSessionHasErrors('diplomas_to_delete');

    expect($otherSubmission->fresh()->diplomas)->toHaveCount(1)
        ->and(Diploma::find($otherDiploma->id))->not->toBeNull();
});

it('requires documents only for document dossier campaigns, not criteria questionnaires', function () {
    ['campaign' => $documentCampaign, 'open' => $documentPosition] = recruitmentFlowCampaign();
    $documentToken = app(InvitationService::class)->createToken(Agent::factory()->create(), $documentCampaign);

    $this->post(route('candidate.save', ['token' => $documentToken->token]), [
        'position_id' => $documentPosition->id,
        'current_structure' => 'Hôpital',
        'current_service' => 'Service',
    ])->assertSessionHasErrors(['cv', 'new_diplomas']);

    ['campaign' => $criteriaCampaign, 'open' => $criteriaPosition] = recruitmentFlowCampaign(CampaignFormType::CriteriaQuestionnaire);
    $criteriaToken = app(InvitationService::class)->createToken(Agent::factory()->create(), $criteriaCampaign);

    $submission = recruitmentFlowSubmitCriteria($this, $criteriaToken->token, $criteriaPosition);

    expect($submission->cv_path)->toBeNull()
        ->and($submission->diplomas)->toHaveCount(0);
});

it('returns 404 when an authenticated admin downloads a missing private file', function () {
    ['campaign' => $campaign, 'open' => $position] = recruitmentFlowCampaign();
    $token = app(InvitationService::class)->createToken(Agent::factory()->create(), $campaign);
    ['submission' => $submission, 'diploma' => $diploma] = recruitmentFlowSubmitDocument($this, $token->token, $position);

    Storage::disk(config('recrutement.storage_disk'))->delete($submission->cv_path);
    Storage::disk(config('recrutement.storage_disk'))->delete($diploma->file_path);

    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.files.cv', ['submission' => $submission]))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('admin.files.diploma', ['diploma' => $diploma]))
        ->assertNotFound();
});
