<?php

use App\Enums\SubmissionStatus;
use App\Exceptions\InvalidSubmissionFileException;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Diploma;
use App\Models\Position;
use App\Services\InvitationService;
use App\Services\SubmissionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('recrutement.storage_disk'));
    $this->service = app(SubmissionService::class);

    $this->agent = Agent::factory()->create();
    $this->campaign = Campaign::factory()->create();
    $this->position = Position::factory()->create(['campaign_id' => $this->campaign->id]);
    $this->token = app(InvitationService::class)->createToken($this->agent, $this->campaign);
});

function validSubmissionData(int $positionId, array $overrides = []): array
{
    return array_merge([
        'position_id' => $positionId,
        'current_structure' => 'Hôpital Principal',
        'current_service' => 'Cardiologie',
    ], $overrides);
}

it('creates a submission with the editable fields and stores the CV under the token folder', function () {
    $cv = UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf');

    $submission = $this->service->saveDraft($this->token, validSubmissionData($this->position->id, [
        'service_entry_date' => '2018-03-01',
        'motivation_note' => 'Très motivé.',
    ]), $cv);

    expect($submission->agent_id)->toBe($this->agent->id);
    expect($submission->position_id)->toBe($this->position->id);
    expect($submission->invitation_token_id)->toBe($this->token->id);
    expect($submission->current_structure)->toBe('Hôpital Principal');
    expect($submission->current_service)->toBe('Cardiologie');
    expect($submission->service_entry_date->format('Y-m-d'))->toBe('2018-03-01');
    expect($submission->motivation_note)->toBe('Très motivé.');

    expect($submission->cv_path)->toBe("submissions/{$this->token->token}/cv.pdf");
    Storage::disk(config('recrutement.storage_disk'))
        ->assertExists("submissions/{$this->token->token}/cv.pdf");
});

it('updates the existing submission instead of creating a duplicate', function () {
    $first = $this->service->saveDraft($this->token, validSubmissionData($this->position->id, [
        'current_structure' => 'Initial',
    ]), UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'));

    $second = $this->service->saveDraft($this->token, [
        'current_structure' => 'Modifié',
    ], null);

    expect($second->id)->toBe($first->id);
    expect($second->current_structure)->toBe('Modifié');
    expect($second->position_id)->toBe($this->position->id);
});

it('replaces the previous CV by deleting the old file before storing the new one', function () {
    $disk = Storage::disk(config('recrutement.storage_disk'));

    $oldCv = UploadedFile::fake()->createWithContent('old.pdf', 'OLD-CV-CONTENT');
    $this->service->saveDraft($this->token, validSubmissionData($this->position->id), $oldCv);
    $path = "submissions/{$this->token->token}/cv.pdf";
    $disk->assertExists($path);
    expect($disk->get($path))->toBe('OLD-CV-CONTENT');

    $newCv = UploadedFile::fake()->createWithContent('new.pdf', 'NEW-CV-CONTENT');
    $this->service->saveDraft($this->token, [], $newCv);

    $disk->assertExists($path);
    expect($disk->get($path))->toBe('NEW-CV-CONTENT');
});

it('marks submitted_at on the first complete submission and only last_updated_at thereafter', function () {
    $cv = UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

    $first = $this->service->saveDraft($this->token, validSubmissionData($this->position->id), $cv);

    expect($first->submitted_at)->not->toBeNull();
    expect($first->last_updated_at)->not->toBeNull();
    expect($first->status)->toBe(SubmissionStatus::Submitted);

    $submittedAt = $first->submitted_at;

    $this->travel(1)->minutes();

    $second = $this->service->saveDraft($this->token, [
        'current_structure' => 'Updated',
    ], null);

    expect($second->submitted_at->equalTo($submittedAt))->toBeTrue();
    expect($second->last_updated_at->greaterThan($submittedAt))->toBeTrue();
});

it('rejects an initial submission without a CV', function () {
    expect(fn () => $this->service->saveDraft($this->token, validSubmissionData($this->position->id), null))
        ->toThrow(RuntimeException::class, 'Le CV est obligatoire.');
});

it('rejects a CV that is not a PDF', function () {
    $cv = UploadedFile::fake()->create('cv.docx', 100, 'application/msword');

    expect(fn () => $this->service->saveDraft($this->token, validSubmissionData($this->position->id), $cv))
        ->toThrow(InvalidSubmissionFileException::class);
});

it('rejects a CV that exceeds the configured max size', function () {
    $maxKb = (int) config('recrutement.upload_max_size_kb');
    $cv = UploadedFile::fake()->create('cv.pdf', $maxKb + 10, 'application/pdf');

    expect(fn () => $this->service->saveDraft($this->token, validSubmissionData($this->position->id), $cv))
        ->toThrow(InvalidSubmissionFileException::class);
});

it('exposes seniority_years computed from service_entry_date', function () {
    $submission = $this->service->saveDraft($this->token, validSubmissionData($this->position->id, [
        'service_entry_date' => now()->subYears(8)->format('Y-m-d'),
    ]), UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'));

    expect($submission->seniority_years)->toBe(8);
});

it('adds a diploma stored in the diplomas subfolder of the token', function () {
    $submission = $this->service->saveDraft(
        $this->token,
        validSubmissionData($this->position->id),
        UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')
    );

    $file = UploadedFile::fake()->create('diplome.pdf', 200, 'application/pdf');
    $diploma = $this->service->addDiploma($submission, [
        'title' => 'Master MIAGE',
        'institution' => 'UCAD',
        'year' => 2018,
    ], $file);

    expect($diploma)->toBeInstanceOf(Diploma::class);
    expect($diploma->submission_id)->toBe($submission->id);
    expect($diploma->title)->toBe('Master MIAGE');
    expect($diploma->institution)->toBe('UCAD');
    expect($diploma->year)->toBe(2018);
    expect($diploma->file_path)
        ->toStartWith("submissions/{$this->token->token}/diplomas/")
        ->toEndWith('.pdf');

    Storage::disk(config('recrutement.storage_disk'))->assertExists($diploma->file_path);
});

it('rejects a diploma file that is not a PDF', function () {
    $submission = $this->service->saveDraft(
        $this->token,
        validSubmissionData($this->position->id),
        UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')
    );
    $file = UploadedFile::fake()->create('d.txt', 50, 'text/plain');

    expect(fn () => $this->service->addDiploma($submission, [
        'title' => 'X',
    ], $file))->toThrow(InvalidSubmissionFileException::class);
});

it('removes a diploma file and record', function () {
    $submission = $this->service->saveDraft(
        $this->token,
        validSubmissionData($this->position->id),
        UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')
    );
    $file = UploadedFile::fake()->create('diplome.pdf', 100, 'application/pdf');
    $diploma = $this->service->addDiploma($submission, ['title' => 'Licence'], $file);
    $path = $diploma->file_path;

    $this->service->removeDiploma($diploma);

    Storage::disk(config('recrutement.storage_disk'))->assertMissing($path);
    expect(Diploma::find($diploma->id))->toBeNull();
});
