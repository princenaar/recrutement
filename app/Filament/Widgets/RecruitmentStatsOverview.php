<?php

namespace App\Filament\Widgets;

use App\Enums\CampaignStatus;
use App\Enums\SubmissionStatus;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\InvitationToken;
use App\Models\Submission;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecruitmentStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    /**
     * @return array<string, int|float>
     */
    public function getMetrics(): array
    {
        $invitationCount = InvitationToken::query()->count();
        $submittedCount = Submission::query()->whereNotNull('submitted_at')->count();

        return [
            'active_campaigns' => Campaign::query()
                ->where('status', CampaignStatus::Active)
                ->count(),
            'candidates' => Agent::query()->count(),
            'active_invitations' => InvitationToken::active()->count(),
            'submitted_submissions' => $submittedCount,
            'shortlisted_submissions' => Submission::query()
                ->where('status', SubmissionStatus::Shortlisted)
                ->count(),
            'rejected_submissions' => Submission::query()
                ->where('status', SubmissionStatus::Rejected)
                ->count(),
            'submission_rate' => $invitationCount > 0
                ? round($submittedCount / $invitationCount * 100, 1)
                : 0.0,
            'scored_submissions' => Submission::query()
                ->whereNotNull('normalized_score')
                ->count(),
            'average_score' => round((float) Submission::query()
                ->whereNotNull('normalized_score')
                ->avg('normalized_score'), 1),
        ];
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $metrics = $this->getMetrics();

        return [
            Stat::make('Campagnes actives', $metrics['active_campaigns'])
                ->description('Campagnes ouvertes au recrutement')
                ->icon('heroicon-o-megaphone')
                ->color('success'),
            Stat::make('Candidats importés', $metrics['candidates'])
                ->description('Candidats disponibles dans la base')
                ->icon('heroicon-o-users')
                ->color('info'),
            Stat::make('Invitations actives', $metrics['active_invitations'])
                ->description('Liens non expirés et non révoqués')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning'),
            Stat::make('Candidatures soumises', $metrics['submitted_submissions'])
                ->description($metrics['submission_rate'].' % des invitations')
                ->icon('heroicon-o-document-check')
                ->color('primary'),
            Stat::make('Présélectionnées', $metrics['shortlisted_submissions'])
                ->description('Dossiers retenus pour la suite')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Rejetées', $metrics['rejected_submissions'])
                ->description('Dossiers non retenus')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make('Score moyen', $metrics['average_score'].' / 100')
                ->description($metrics['scored_submissions'].' dossier(s) scoré(s)')
                ->icon('heroicon-o-chart-bar')
                ->color('gray'),
        ];
    }
}
