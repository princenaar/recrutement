<?php

namespace App\Filament\Widgets;

use App\Models\InvitationToken;
use App\Models\Submission;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvitationFunnelStats extends StatsOverviewWidget
{
    protected static ?int $sort = 40;

    protected ?string $heading = 'Suivi des invitations';

    /**
     * @return array<string, int|float>
     */
    public function getMetrics(): array
    {
        $total = InvitationToken::query()->count();
        $converted = InvitationToken::query()
            ->whereHas('submission', fn ($query) => $query->whereNotNull('submitted_at'))
            ->count();

        return [
            'created' => $total,
            'active' => InvitationToken::active()->count(),
            'expired' => InvitationToken::query()
                ->whereNull('revoked_at')
                ->where('expires_at', '<=', now())
                ->count(),
            'revoked' => InvitationToken::query()
                ->whereNotNull('revoked_at')
                ->count(),
            'converted' => $converted,
            'without_submission' => $total - $converted,
            'conversion_rate' => $total > 0 ? round($converted / $total * 100, 1) : 0.0,
            'submitted_submissions' => Submission::query()
                ->whereNotNull('submitted_at')
                ->count(),
        ];
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $metrics = $this->getMetrics();

        return [
            Stat::make('Invitations créées', $metrics['created'])
                ->description('Tous canaux confondus')
                ->icon('heroicon-o-envelope')
                ->color('info'),
            Stat::make('Actives', $metrics['active'])
                ->description('Liens encore utilisables')
                ->icon('heroicon-o-link')
                ->color('success'),
            Stat::make('Expirées', $metrics['expired'])
                ->description('Liens arrivés à échéance')
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Révoquées', $metrics['revoked'])
                ->description('Liens désactivés manuellement')
                ->icon('heroicon-o-no-symbol')
                ->color('danger'),
            Stat::make('Converties', $metrics['converted'])
                ->description($metrics['conversion_rate'].' % de conversion')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('primary'),
            Stat::make('Sans candidature', $metrics['without_submission'])
                ->description('Invitations sans dossier soumis')
                ->icon('heroicon-o-document-minus')
                ->color('gray'),
        ];
    }
}
