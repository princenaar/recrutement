<?php

namespace App\Filament\Widgets;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use Filament\Widgets\ChartWidget;

class SubmissionStatusChart extends ChartWidget
{
    protected static ?int $sort = 30;

    protected ?string $heading = 'Répartition des candidatures';

    protected ?string $description = 'Dossiers soumis par statut de traitement.';

    protected string $color = 'success';

    /**
     * @return array<string, int>
     */
    public function getStatusCounts(): array
    {
        $counts = Submission::query()
            ->whereNotNull('submitted_at')
            ->whereIn('status', [
                SubmissionStatus::Submitted,
                SubmissionStatus::UnderReview,
                SubmissionStatus::Shortlisted,
                SubmissionStatus::Rejected,
            ])
            ->get(['status'])
            ->groupBy(fn (Submission $submission): string => $submission->status->value)
            ->map->count();

        return collect([
            SubmissionStatus::Submitted,
            SubmissionStatus::UnderReview,
            SubmissionStatus::Shortlisted,
            SubmissionStatus::Rejected,
        ])
            ->mapWithKeys(fn (SubmissionStatus $status): array => [
                $status->getLabel() => (int) ($counts[$status->value] ?? 0),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getChartData(): array
    {
        $counts = $this->getStatusCounts();

        return [
            'datasets' => [
                [
                    'label' => 'Candidatures',
                    'data' => array_values($counts),
                    'backgroundColor' => [
                        '#3b82f6',
                        '#f59e0b',
                        '#10b981',
                        '#ef4444',
                    ],
                ],
            ],
            'labels' => array_keys($counts),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        return $this->getChartData();
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
