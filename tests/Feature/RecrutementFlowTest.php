<?php

use App\Enums\CampaignStatus;
use App\Enums\InvitationChannel;
use App\Enums\PositionStatus;
use App\Enums\SubmissionStatus;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Position;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Services\AgentImportService;
use App\Services\InvitationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('runs the complete recruitment flow end-to-end', function () {
    Notification::fake();
    Storage::fake(config('recrutement.storage_disk'));

    // 1. Admin imports the iHRIS Excel fixture → 5 agents
    $importResult = app(AgentImportService::class)->import(base_path('tests/fixtures/agents_sample.xlsx'));
    expect($importResult->created)->toBe(5);
    expect(Agent::count())->toBe(5);

    // 2. Admin creates a campaign + 2 positions
    $campaign = Campaign::create([
        'title' => 'Recrutement DSI 2026',
        'description' => 'Campagne de test',
        'status' => CampaignStatus::Active,
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);
    $position = Position::create([
        'campaign_id' => $campaign->id,
        'title' => 'Ingénieur DevOps',
        'status' => PositionStatus::Open,
    ]);
    $secondPosition = Position::create([
        'campaign_id' => $campaign->id,
        'title' => 'Chef de projet SI',
        'status' => PositionStatus::Open,
    ]);

    // 3. Admin invites an agent who has an email → email notification
    $agentWithEmail = Agent::query()->whereNotNull('email')->first();
    expect($agentWithEmail)->not->toBeNull();

    $invitations = app(InvitationService::class);
    $tokenWithEmail = $invitations->createToken($agentWithEmail, $campaign);
    $manualMessage = $invitations->sendInvitation($tokenWithEmail);

    expect($manualMessage)->toBeNull();
    Notification::assertSentTo($agentWithEmail, InvitationNotification::class);
    $tokenWithEmail->refresh();
    expect($tokenWithEmail->notification_channel)->toBe(InvitationChannel::Email);

    // 4. Admin invites an agent without email → manual text returned
    $agentWithoutEmail = Agent::query()->whereNull('email')->first();

    if ($agentWithoutEmail === null) {
        $agentWithoutEmail = Agent::factory()->create(['email' => null, 'first_name' => 'Sans', 'last_name' => 'Email']);
    }

    $tokenManual = $invitations->createToken($agentWithoutEmail, $campaign);
    $manualText = $invitations->sendInvitation($tokenManual);

    expect($manualText)->toBeString();
    expect($manualText)->toContain($agentWithoutEmail->first_name);
    expect($manualText)->toContain($tokenManual->token);
    expect($manualText)->toContain('Recrutement DSI 2026');
    $tokenManual->refresh();
    expect($tokenManual->notification_channel)->toBe(InvitationChannel::Manual);

    // 5. Agent visits the portal URL → form is prefilled with iHRIS data and lists open positions
    $portalResponse = $this->get(route('candidate.portal', ['token' => $tokenWithEmail->token]));
    $portalResponse->assertOk()
        ->assertViewIs('candidate.portal')
        ->assertSee($agentWithEmail->matricule)
        ->assertSee($agentWithEmail->first_name)
        ->assertSee($position->title)
        ->assertSee($secondPosition->title);

    // 6. Agent picks a position, POSTs the form + CV → submitted_at is set, file stored
    $cv = UploadedFile::fake()->createWithContent('cv.pdf', 'PDF-CONTENT-V1');
    $diplomaFile = UploadedFile::fake()->createWithContent('diplome.pdf', 'PDF-DIPLOMA');
    $this->post(route('candidate.save', ['token' => $tokenWithEmail->token]), [
        'position_id' => $position->id,
        'current_structure' => 'Hôpital Le Dantec',
        'current_service' => 'DSI',
        'service_entry_date' => now()->subYears(6)->format('Y-m-d'),
        'motivation_note' => 'Très intéressé par ce poste.',
        'cv' => $cv,
        'new_diplomas' => [
            [
                'title' => 'Master MIAGE',
                'institution' => 'UCAD',
                'year' => 2018,
                'file' => $diplomaFile,
            ],
        ],
    ])->assertRedirect();

    $submission = Submission::where('agent_id', $agentWithEmail->id)->where('position_id', $position->id)->first();
    expect($submission)->not->toBeNull();
    expect($submission->submitted_at)->not->toBeNull();
    expect($submission->status)->toBe(SubmissionStatus::Submitted);
    expect($submission->current_structure)->toBe('Hôpital Le Dantec');
    expect($submission->seniority_years)->toBe(6);

    Storage::disk(config('recrutement.storage_disk'))
        ->assertExists("submissions/{$tokenWithEmail->token}/cv.pdf");

    $firstSubmittedAt = $submission->submitted_at;

    // 7. Agent re-POSTs → last_updated_at advances, submitted_at unchanged, position locked
    $this->travel(2)->minutes();

    $this->post(route('candidate.save', ['token' => $tokenWithEmail->token]), [
        'position_id' => $secondPosition->id, // tries to switch — should be ignored
        'current_structure' => 'Hôpital Principal',
        'current_service' => 'DSI',
        'motivation_note' => 'Mise à jour de ma motivation.',
    ])->assertRedirect();

    $submission->refresh();
    expect($submission->submitted_at->equalTo($firstSubmittedAt))->toBeTrue();
    expect($submission->last_updated_at->greaterThan($firstSubmittedAt))->toBeTrue();
    expect($submission->current_structure)->toBe('Hôpital Principal');
    expect($submission->position_id)->toBe($position->id); // locked

    // 8. Admin shortlists the candidate
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $submission->update([
        'status' => SubmissionStatus::Shortlisted,
        'shortlisted_at' => now(),
        'shortlisted_by' => $admin->id,
    ]);
    expect($submission->fresh()->status)->toBe(SubmissionStatus::Shortlisted);
    expect($submission->fresh()->shortlisted_by)->toBe($admin->id);

    // 9. Admin rejects another submission with a note
    $rejectedSubmission = Submission::create([
        'invitation_token_id' => $tokenManual->id,
        'agent_id' => $agentWithoutEmail->id,
        'position_id' => $position->id,
        'current_structure' => 'Hôpital Principal',
        'current_service' => 'DSI',
        'cv_path' => "submissions/{$tokenManual->token}/cv.pdf",
        'status' => SubmissionStatus::Submitted,
        'submitted_at' => now(),
        'last_updated_at' => now(),
    ]);
    $rejectedSubmission->update([
        'status' => SubmissionStatus::Rejected,
        'rejection_note' => 'Profil non aligné avec les exigences du poste.',
    ]);

    $rejectedSubmission->refresh();
    expect($rejectedSubmission->status)->toBe(SubmissionStatus::Rejected);
    expect($rejectedSubmission->rejection_note)->toBe('Profil non aligné avec les exigences du poste.');
});

it('blocks portal access when token is revoked or expired even after a submission exists', function () {
    Storage::fake(config('recrutement.storage_disk'));

    $agent = Agent::factory()->create();
    $campaign = Campaign::factory()->create();
    $position = Position::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => PositionStatus::Open,
    ]);
    $token = app(InvitationService::class)->createToken($agent, $campaign);

    // Submit normally first
    $cv = UploadedFile::fake()->createWithContent('cv.pdf', 'CV');
    $diplomaFile = UploadedFile::fake()->createWithContent('diplome.pdf', 'PDF');
    $this->post(route('candidate.save', ['token' => $token->token]), [
        'position_id' => $position->id,
        'current_structure' => 'Initial',
        'current_service' => 'Service',
        'cv' => $cv,
        'new_diplomas' => [
            [
                'title' => 'Licence',
                'file' => $diplomaFile,
            ],
        ],
    ])->assertRedirect();

    // Token gets revoked → portal access is blocked even though submission exists
    $token->update(['revoked_at' => now()]);

    $this->get(route('candidate.portal', ['token' => $token->token]))
        ->assertOk()
        ->assertViewIs('candidate.error');

    // Submission survives in DB for the admin
    expect(Submission::where('agent_id', $agent->id)->exists())->toBeTrue();
});
