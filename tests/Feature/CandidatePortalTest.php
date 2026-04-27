<?php

use App\Enums\PositionStatus;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Diploma;
use App\Models\Position;
use App\Models\Submission;
use App\Services\InvitationService;
use App\Services\SubmissionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    Storage::fake(config('recrutement.storage_disk'));

    $this->agent = Agent::factory()->create([
        'first_name' => 'Awa',
        'last_name' => 'DIOP',
        'matricule' => 'MAT123456',
        'structure' => 'Hôpital Principal',
    ]);
    $this->campaign = Campaign::factory()->create(['title' => 'Recrutement DSI 2026']);
    $this->position = Position::factory()->create([
        'campaign_id' => $this->campaign->id,
        'title' => 'Ingénieur DevOps',
        'required_profile' => 'Au moins cinq ans d\'expérience en administration systèmes.',
        'status' => PositionStatus::Open,
    ]);
    $this->token = app(InvitationService::class)->createToken($this->agent, $this->campaign);
});

it('returns 404 for an unknown token', function () {
    $this->get(route('candidate.portal', ['token' => 'unknown-uuid']))
        ->assertNotFound();
});

it('shows the revoked error view for a revoked token', function () {
    $this->token->update(['revoked_at' => now()]);

    $this->get(route('candidate.portal', ['token' => $this->token->token]))
        ->assertOk()
        ->assertViewIs('candidate.error')
        ->assertSee('Ce lien n\'est plus valide', false);
});

it('shows the expired view when the token has expired', function () {
    $this->token->update(['expires_at' => now()->subMinute()]);

    $this->get(route('candidate.portal', ['token' => $this->token->token]))
        ->assertOk()
        ->assertViewIs('candidate.expired')
        ->assertSee('Ce lien d\'invitation a expiré', false);
});

it('renders the portal with agent data and open positions for a valid token', function () {
    $response = $this->get(route('candidate.portal', ['token' => $this->token->token]));

    $response->assertOk()
        ->assertViewIs('candidate.portal')
        ->assertSee('MAT123456')
        ->assertSee('Awa')
        ->assertSee('DIOP')
        ->assertSee('Recrutement DSI 2026')
        ->assertSee('Ingénieur DevOps')
        ->assertSee('Au moins cinq ans d&#039;expérience en administration systèmes.', false);
});

it('saves the editable fields, position choice and CV via POST', function () {
    $cv = UploadedFile::fake()->createWithContent('cv.pdf', 'PDF-DATA');
    $firstDiplomaFile = UploadedFile::fake()->createWithContent('master.pdf', 'PDF-DIPLOMA-1');
    $secondDiplomaFile = UploadedFile::fake()->createWithContent('licence.pdf', 'PDF-DIPLOMA-2');

    $response = $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $this->position->id,
        'current_structure' => 'Hôpital Le Dantec',
        'current_service' => 'Pédiatrie',
        'service_entry_date' => '2018-04-15',
        'motivation_note' => 'Voici ma motivation.',
        'cv' => $cv,
        'new_diplomas' => [
            [
                'title' => 'Master MIAGE',
                'institution' => 'UCAD',
                'year' => 2018,
                'file' => $firstDiplomaFile,
            ],
            [
                'title' => 'Licence Informatique',
                'institution' => 'UGB',
                'year' => 2015,
                'file' => $secondDiplomaFile,
            ],
        ],
    ]);

    $response->assertRedirect();

    $submission = Submission::where('agent_id', $this->agent->id)->first();
    expect($submission)->not->toBeNull();
    expect($submission->position_id)->toBe($this->position->id);
    expect($submission->current_structure)->toBe('Hôpital Le Dantec');
    expect($submission->current_service)->toBe('Pédiatrie');
    expect($submission->service_entry_date->format('Y-m-d'))->toBe('2018-04-15');
    expect($submission->motivation_note)->toBe('Voici ma motivation.');
    expect($submission->submitted_at)->not->toBeNull();

    Storage::disk(config('recrutement.storage_disk'))
        ->assertExists("submissions/{$this->token->token}/cv.pdf");

    expect($submission->diplomas)->toHaveCount(2);
    expect($submission->diplomas->pluck('title')->all())->toBe(['Master MIAGE', 'Licence Informatique']);
});

it('requires the selected position, structure, service, CV and at least one diploma', function () {
    $this->post(route('candidate.save', ['token' => $this->token->token]), [])
        ->assertSessionHasErrors([
            'position_id',
            'current_structure',
            'current_service',
            'cv',
            'new_diplomas',
        ]);

    expect(Submission::count())->toBe(0);
    expect(Diploma::count())->toBe(0);
});

it('rejects a position that is not part of the campaign', function () {
    $otherCampaign = Campaign::factory()->create();
    $foreignPosition = Position::factory()->create([
        'campaign_id' => $otherCampaign->id,
        'status' => PositionStatus::Open,
    ]);

    $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $foreignPosition->id,
        'current_structure' => 'Hôpital',
        'current_service' => 'Service',
        'cv' => UploadedFile::fake()->createWithContent('cv.pdf', 'PDF'),
        'new_diplomas' => [
            [
                'title' => 'Licence',
                'file' => UploadedFile::fake()->createWithContent('diplome.pdf', 'PDF'),
            ],
        ],
    ])->assertSessionHasErrors('position_id');

    expect(Submission::count())->toBe(0);
});

it('rejects a foreign position when the submission service is called directly', function () {
    $otherCampaign = Campaign::factory()->create();
    $foreignPosition = Position::factory()->create([
        'campaign_id' => $otherCampaign->id,
        'status' => PositionStatus::Open,
    ]);

    expect(fn () => app(SubmissionService::class)->saveDraft($this->token, [
        'position_id' => $foreignPosition->id,
    ], null))->toThrow(RuntimeException::class);

    expect(Submission::count())->toBe(0);
});

it('renders the submitted confirmation content inside the candidate layout', function () {
    $html = view('candidate.submitted', [
        'errors' => new ViewErrorBag,
        'token' => $this->token,
    ])->render();

    expect($html)->toContain('Votre dossier a bien été enregistré');
    expect($html)->toContain('Retour à mon dossier');
});

it('locks the position once a submission has been submitted', function () {
    $cv = UploadedFile::fake()->createWithContent('cv.pdf', 'PDF');
    $diplomaFile = UploadedFile::fake()->createWithContent('diplome.pdf', 'PDF');
    $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $this->position->id,
        'current_structure' => 'Hôpital',
        'current_service' => 'Service',
        'cv' => $cv,
        'new_diplomas' => [
            [
                'title' => 'Licence',
                'file' => $diplomaFile,
            ],
        ],
    ])->assertRedirect();

    $secondPosition = Position::factory()->create([
        'campaign_id' => $this->campaign->id,
        'status' => PositionStatus::Open,
    ]);

    // Even if the agent tries to switch position, the original is preserved
    $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $secondPosition->id,
        'current_structure' => 'Updated',
        'current_service' => 'Service',
    ])->assertRedirect();

    $submission = Submission::where('agent_id', $this->agent->id)->first();
    expect($submission->position_id)->toBe($this->position->id);
    expect($submission->current_structure)->toBe('Updated');
});

it('ignores attempts to overwrite iHRIS fields via POST', function () {
    $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $this->position->id,
        'current_structure' => 'Nouvelle Structure',
        'current_service' => 'Service',
        'cv' => UploadedFile::fake()->createWithContent('cv.pdf', 'PDF'),
        'new_diplomas' => [
            [
                'title' => 'Licence',
                'file' => UploadedFile::fake()->createWithContent('diplome.pdf', 'PDF'),
            ],
        ],
        'matricule' => 'HACKED',
        'first_name' => 'Hacker',
        'agent_id' => 999,
    ]);

    $this->agent->refresh();
    expect($this->agent->matricule)->toBe('MAT123456');
    expect($this->agent->first_name)->toBe('Awa');

    $submission = Submission::where('agent_id', $this->agent->id)->first();
    expect($submission->agent_id)->toBe($this->agent->id);
});

it('rejects an expired token on POST', function () {
    $this->token->update(['expires_at' => now()->subMinute()]);

    $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $this->position->id,
        'current_structure' => 'Test',
        'current_service' => 'Service',
        'cv' => UploadedFile::fake()->createWithContent('cv.pdf', 'PDF'),
        'new_diplomas' => [
            [
                'title' => 'Licence',
                'file' => UploadedFile::fake()->createWithContent('diplome.pdf', 'PDF'),
            ],
        ],
    ])->assertOk()->assertViewIs('candidate.expired');

    expect(Submission::count())->toBe(0);
});

it('adds a diploma through the main submission form after a complete submission has been saved', function () {
    // First, save a complete submission so the candidate can add another diploma.
    $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $this->position->id,
        'current_structure' => 'Hôpital',
        'current_service' => 'Service',
        'cv' => UploadedFile::fake()->createWithContent('cv.pdf', 'PDF'),
        'new_diplomas' => [
            [
                'title' => 'Licence',
                'file' => UploadedFile::fake()->createWithContent('licence.pdf', 'PDF'),
            ],
        ],
    ])->assertRedirect();

    $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $this->position->id,
        'current_structure' => 'Hôpital',
        'current_service' => 'Service',
        'new_diplomas' => [
            [
                'title' => 'Master MIAGE',
                'institution' => 'UCAD',
                'year' => 2018,
                'file' => UploadedFile::fake()->createWithContent('master.pdf', 'PDF-DATA'),
            ],
        ],
    ])->assertRedirect();

    $diploma = Diploma::query()->latest('id')->first();
    expect($diploma)->not->toBeNull();
    expect($diploma->title)->toBe('Master MIAGE');
    Storage::disk(config('recrutement.storage_disk'))->assertExists($diploma->file_path);
});

it('removes a diploma through the main submission form', function () {
    $submission = Submission::factory()->create([
        'agent_id' => $this->agent->id,
        'position_id' => $this->position->id,
        'invitation_token_id' => $this->token->id,
    ]);
    $diploma = Diploma::factory()->create([
        'submission_id' => $submission->id,
        'file_path' => "submissions/{$this->token->token}/diplomas/abc.pdf",
    ]);
    Storage::disk(config('recrutement.storage_disk'))->put($diploma->file_path, 'data');

    $this->post(route('candidate.save', [
        'token' => $this->token->token,
    ]), [
        'position_id' => $this->position->id,
        'current_structure' => 'Hôpital Principal',
        'current_service' => 'Service',
        'diplomas_to_delete' => [$diploma->id],
        'new_diplomas' => [
            [
                'title' => 'Master MIAGE',
                'file' => UploadedFile::fake()->createWithContent('master.pdf', 'PDF-DATA'),
            ],
        ],
    ])->assertRedirect();

    expect(Diploma::find($diploma->id))->toBeNull();
    Storage::disk(config('recrutement.storage_disk'))->assertMissing($diploma->file_path);
    expect($submission->fresh()->diplomas)->toHaveCount(1);
});

it('refuses to remove the last diploma without a replacement', function () {
    $submission = Submission::factory()->create([
        'agent_id' => $this->agent->id,
        'position_id' => $this->position->id,
        'invitation_token_id' => $this->token->id,
    ]);
    $diploma = Diploma::factory()->create(['submission_id' => $submission->id]);

    $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $this->position->id,
        'current_structure' => 'Hôpital Principal',
        'current_service' => 'Service',
        'diplomas_to_delete' => [$diploma->id],
    ])->assertSessionHasErrors('new_diplomas');

    expect(Diploma::find($diploma->id))->not->toBeNull();
});

it('refuses to remove a diploma belonging to another token through the main form', function () {
    $otherAgent = Agent::factory()->create();
    $otherCampaign = Campaign::factory()->create();
    $otherPosition = Position::factory()->create(['campaign_id' => $otherCampaign->id]);
    $otherToken = app(InvitationService::class)->createToken($otherAgent, $otherCampaign);
    $otherSubmission = Submission::factory()->create([
        'agent_id' => $otherAgent->id,
        'position_id' => $otherPosition->id,
        'invitation_token_id' => $otherToken->id,
    ]);
    $otherDiploma = Diploma::factory()->create(['submission_id' => $otherSubmission->id]);

    $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $this->position->id,
        'current_structure' => 'Hôpital Principal',
        'current_service' => 'Service',
        'cv' => UploadedFile::fake()->createWithContent('cv.pdf', 'PDF'),
        'diplomas_to_delete' => [$otherDiploma->id],
        'new_diplomas' => [
            [
                'title' => 'Licence',
                'file' => UploadedFile::fake()->createWithContent('licence.pdf', 'PDF'),
            ],
        ],
    ])->assertSessionHasErrors('diplomas_to_delete');

    expect(Diploma::find($otherDiploma->id))->not->toBeNull();
});
