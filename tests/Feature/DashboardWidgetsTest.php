<?php

use App\Enums\CampaignStatus;
use App\Enums\SubmissionStatus;
use App\Filament\Widgets\InvitationFunnelStats;
use App\Filament\Widgets\RecruitmentStatsOverview;
use App\Filament\Widgets\SubmissionsPerMonthChart;
use App\Filament\Widgets\SubmissionStatusChart;
use App\Filament\Widgets\TopCampaignsWidget;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\InvitationToken;
use App\Models\Position;
use App\Models\Submission;

it('provides recruitment overview metrics', function () {
    $campaign = Campaign::factory()->create(['status' => CampaignStatus::Active]);
    Campaign::factory()->create(['status' => CampaignStatus::Closed]);
    $position = Position::factory()->create(['campaign_id' => $campaign->id]);
    $agents = Agent::factory()->count(5)->create();
    $submittedToken = InvitationToken::factory()->create([
        'agent_id' => $agents[0]->id,
        'campaign_id' => $campaign->id,
        'expires_at' => now()->addDays(2),
    ]);
    $shortlistedToken = InvitationToken::factory()->create([
        'agent_id' => $agents[1]->id,
        'campaign_id' => $campaign->id,
        'expires_at' => now()->addDays(2),
    ]);
    $rejectedToken = InvitationToken::factory()->create([
        'agent_id' => $agents[2]->id,
        'campaign_id' => $campaign->id,
        'expires_at' => now()->addDays(2),
    ]);
    InvitationToken::factory()->create([
        'agent_id' => $agents[3]->id,
        'campaign_id' => $campaign->id,
        'expires_at' => now()->addDays(2),
    ]);
    InvitationToken::factory()->create([
        'agent_id' => $agents[4]->id,
        'campaign_id' => $campaign->id,
        'expires_at' => now()->subDay(),
    ]);

    Submission::factory()->create([
        'agent_id' => $agents[0]->id,
        'position_id' => $position->id,
        'invitation_token_id' => $submittedToken->id,
        'submitted_at' => now(),
        'status' => SubmissionStatus::Submitted,
    ]);
    Submission::factory()->create([
        'agent_id' => $agents[1]->id,
        'position_id' => $position->id,
        'invitation_token_id' => $shortlistedToken->id,
        'submitted_at' => now(),
        'status' => SubmissionStatus::Shortlisted,
        'normalized_score' => 80,
    ]);
    Submission::factory()->create([
        'agent_id' => $agents[2]->id,
        'position_id' => $position->id,
        'invitation_token_id' => $rejectedToken->id,
        'submitted_at' => now(),
        'status' => SubmissionStatus::Rejected,
        'normalized_score' => 60,
    ]);

    $metrics = app(RecruitmentStatsOverview::class)->getMetrics();

    expect($metrics['active_campaigns'])->toBe(1)
        ->and($metrics['candidates'])->toBe(5)
        ->and($metrics['active_invitations'])->toBe(4)
        ->and($metrics['submitted_submissions'])->toBe(3)
        ->and($metrics['shortlisted_submissions'])->toBe(1)
        ->and($metrics['rejected_submissions'])->toBe(1)
        ->and($metrics['submission_rate'])->toBe(60.0)
        ->and($metrics['scored_submissions'])->toBe(2)
        ->and($metrics['average_score'])->toBe(70.0);
});

it('builds monthly submission chart data for the last twelve months', function () {
    $firstMonth = now()->startOfMonth()->subMonths(2)->addDay();
    $currentMonth = now()->startOfMonth()->addDays(3);

    Submission::factory()->create(['submitted_at' => $firstMonth]);
    Submission::factory()->create(['submitted_at' => $firstMonth->copy()->addDay()]);
    Submission::factory()->create(['submitted_at' => $currentMonth]);
    Submission::factory()->create(['submitted_at' => null]);

    $series = app(SubmissionsPerMonthChart::class)->getMonthlySeries();

    expect($series['labels'])->toHaveCount(12)
        ->and($series['values'])->toHaveCount(12)
        ->and($series['values'][9])->toBe(2)
        ->and($series['values'][10])->toBe(0)
        ->and($series['values'][11])->toBe(1);
});

it('builds status distribution chart data', function () {
    Submission::factory()->create([
        'submitted_at' => now(),
        'status' => SubmissionStatus::Submitted,
    ]);
    Submission::factory()->create([
        'submitted_at' => now(),
        'status' => SubmissionStatus::UnderReview,
    ]);
    Submission::factory()->create([
        'submitted_at' => now(),
        'status' => SubmissionStatus::Shortlisted,
    ]);
    Submission::factory()->create([
        'submitted_at' => now(),
        'status' => SubmissionStatus::Rejected,
    ]);
    Submission::factory()->create([
        'submitted_at' => null,
        'status' => SubmissionStatus::Draft,
    ]);

    $counts = app(SubmissionStatusChart::class)->getStatusCounts();

    expect($counts)->toBe([
        'Soumise' => 1,
        'En revue' => 1,
        'Présélectionnée' => 1,
        'Rejetée' => 1,
    ]);
});

it('provides invitation funnel metrics', function () {
    $submittedToken = InvitationToken::factory()->create(['expires_at' => now()->addDays(2)]);
    InvitationToken::factory()->create(['expires_at' => now()->addDays(2)]);
    InvitationToken::factory()->create(['expires_at' => now()->subDay()]);
    InvitationToken::factory()->create([
        'expires_at' => now()->addDays(2),
        'revoked_at' => now(),
    ]);

    Submission::factory()->create([
        'invitation_token_id' => $submittedToken->id,
        'agent_id' => $submittedToken->agent_id,
        'submitted_at' => now(),
    ]);

    $metrics = app(InvitationFunnelStats::class)->getMetrics();

    expect($metrics['created'])->toBe(4)
        ->and($metrics['active'])->toBe(2)
        ->and($metrics['expired'])->toBe(1)
        ->and($metrics['revoked'])->toBe(1)
        ->and($metrics['converted'])->toBe(1)
        ->and($metrics['without_submission'])->toBe(3)
        ->and($metrics['conversion_rate'])->toBe(25.0);
});

it('returns top campaign rows ordered by submitted applications', function () {
    $firstCampaign = Campaign::factory()->create(['title' => 'Campagne A']);
    $secondCampaign = Campaign::factory()->create(['title' => 'Campagne B']);
    $firstPosition = Position::factory()->create(['campaign_id' => $firstCampaign->id]);
    $secondPosition = Position::factory()->create(['campaign_id' => $secondCampaign->id]);

    InvitationToken::factory()->count(2)->create(['campaign_id' => $firstCampaign->id]);
    InvitationToken::factory()->create(['campaign_id' => $secondCampaign->id]);

    Submission::factory()->count(2)->create([
        'position_id' => $firstPosition->id,
        'submitted_at' => now(),
        'normalized_score' => 70,
    ]);
    Submission::factory()->create([
        'position_id' => $secondPosition->id,
        'submitted_at' => now(),
        'normalized_score' => 90,
    ]);

    $rows = TopCampaignsWidget::getTopCampaignRows();

    expect($rows->first()['title'])->toBe('Campagne A')
        ->and($rows->first()['submitted_submissions_count'])->toBe(2)
        ->and($rows->first()['invitation_tokens_count'])->toBe(2)
        ->and($rows->first()['average_score'])->toBe(70.0)
        ->and($rows->get(1)['title'])->toBe('Campagne B');
});
