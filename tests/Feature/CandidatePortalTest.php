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
        ->assertSee('Ingénieur DevOps');
});

it('saves the editable fields, position choice and CV via POST', function () {
    $cv = UploadedFile::fake()->createWithContent('cv.pdf', 'PDF-DATA');

    $response = $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $this->position->id,
        'current_structure' => 'Hôpital Le Dantec',
        'current_service' => 'Pédiatrie',
        'service_entry_date' => '2018-04-15',
        'motivation_note' => 'Voici ma motivation.',
        'cv' => $cv,
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
    $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $this->position->id,
        'cv' => $cv,
    ])->assertRedirect();

    $secondPosition = Position::factory()->create([
        'campaign_id' => $this->campaign->id,
        'status' => PositionStatus::Open,
    ]);

    // Even if the agent tries to switch position, the original is preserved
    $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $secondPosition->id,
        'current_structure' => 'Updated',
    ])->assertRedirect();

    $submission = Submission::where('agent_id', $this->agent->id)->first();
    expect($submission->position_id)->toBe($this->position->id);
    expect($submission->current_structure)->toBe('Updated');
});

it('ignores attempts to overwrite iHRIS fields via POST', function () {
    $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $this->position->id,
        'current_structure' => 'Nouvelle Structure',
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
    ])->assertOk()->assertViewIs('candidate.expired');

    expect(Submission::count())->toBe(0);
});

it('adds a diploma via POST after a draft has been saved', function () {
    // First, save a draft so the candidate has a submission
    $this->post(route('candidate.save', ['token' => $this->token->token]), [
        'position_id' => $this->position->id,
    ])->assertRedirect();

    $file = UploadedFile::fake()->createWithContent('d.pdf', 'PDF-DATA');

    $this->post(route('candidate.diploma.add', ['token' => $this->token->token]), [
        'title' => 'Master MIAGE',
        'institution' => 'UCAD',
        'year' => 2018,
        'file' => $file,
    ])->assertRedirect();

    $diploma = Diploma::first();
    expect($diploma)->not->toBeNull();
    expect($diploma->title)->toBe('Master MIAGE');
    Storage::disk(config('recrutement.storage_disk'))->assertExists($diploma->file_path);
});

it('refuses to add a diploma before any submission exists', function () {
    $file = UploadedFile::fake()->createWithContent('d.pdf', 'PDF-DATA');

    $this->post(route('candidate.diploma.add', ['token' => $this->token->token]), [
        'title' => 'Master MIAGE',
        'file' => $file,
    ])->assertSessionHasErrors('file');

    expect(Diploma::count())->toBe(0);
});

it('removes a diploma via DELETE', function () {
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

    $this->delete(route('candidate.diploma.remove', [
        'token' => $this->token->token,
        'diploma' => $diploma->id,
    ]))->assertRedirect();

    expect(Diploma::find($diploma->id))->toBeNull();
    Storage::disk(config('recrutement.storage_disk'))->assertMissing($diploma->file_path);
});

it('refuses to remove a diploma belonging to another token', function () {
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

    $this->delete(route('candidate.diploma.remove', [
        'token' => $this->token->token,
        'diploma' => $otherDiploma->id,
    ]))->assertNotFound();

    expect(Diploma::find($otherDiploma->id))->not->toBeNull();
});
