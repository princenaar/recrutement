<?php

namespace App\Filament\Widgets;

use App\Models\Submission;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class SubmissionsPerMonthChart extends ChartWidget
{
    protected static ?int $sort = 20;

    protected ?string $heading = 'Candidatures par mois';

    protected ?string $description = 'Volume des dossiers soumis sur les 12 derniers mois.';

    protected string $color = 'primary';

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function getMonthlySeries(): array
    {
        $months = collect(range(11, 0))
            ->map(fn (int $monthsAgo): CarbonImmutable => now()
                ->toImmutable()
                ->startOfMonth()
                ->subMonths($monthsAgo));

        $countsByMonth = Submission::query()
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', $months->first()->startOfMonth())
            ->where('submitted_at', '<=', $months->last()->endOfMonth())
            ->get(['submitted_at'])
            ->groupBy(fn (Submission $submission): string => $submission->submitted_at->format('Y-m'))
            ->map->count();

        return [
            'labels' => $months
                ->map(fn (CarbonImmutable $month): string => $month->translatedFormat('M Y'))
                ->all(),
            'values' => $months
                ->map(fn (CarbonImmutable $month): int => (int) ($countsByMonth[$month->format('Y-m')] ?? 0))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getChartData(): array
    {
        $series = $this->getMonthlySeries();

        return [
            'datasets' => [
                [
                    'label' => 'Candidatures soumises',
                    'data' => $series['values'],
                    'fill' => true,
                ],
            ],
            'labels' => $series['labels'],
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
        return 'line';
    }
}
