<?php

namespace App\Filament\Widgets;

use App\Models\Campaign;
use App\Models\Submission;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;

class TopCampaignsWidget extends TableWidget
{
    protected static ?int $sort = 50;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function getTopCampaignRows(int $limit = 5): Collection
    {
        return Campaign::query()
            ->select('campaigns.*')
            ->withCount(['positions', 'invitationTokens'])
            ->selectSub(
                Submission::query()
                    ->join('positions', 'positions.id', '=', 'submissions.position_id')
                    ->whereColumn('positions.campaign_id', 'campaigns.id')
                    ->whereNotNull('submissions.submitted_at')
                    ->selectRaw('count(*)'),
                'submitted_submissions_count',
            )
            ->selectSub(
                Submission::query()
                    ->join('positions', 'positions.id', '=', 'submissions.position_id')
                    ->whereColumn('positions.campaign_id', 'campaigns.id')
                    ->whereNotNull('submissions.normalized_score')
                    ->selectRaw('round(avg(submissions.normalized_score), 1)'),
                'average_score',
            )
            ->orderByDesc('submitted_submissions_count')
            ->limit($limit)
            ->get()
            ->map(fn (Campaign $campaign): array => [
                'title' => $campaign->title,
                'status' => $campaign->status?->getLabel(),
                'positions_count' => $campaign->positions_count,
                'invitation_tokens_count' => $campaign->invitation_tokens_count,
                'submitted_submissions_count' => (int) $campaign->submitted_submissions_count,
                'average_score' => $campaign->average_score !== null
                    ? (float) $campaign->average_score
                    : null,
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Campagnes les plus actives')
            ->query(fn (): EloquentBuilder => Campaign::query()
                ->select('campaigns.*')
                ->withCount(['positions', 'invitationTokens'])
                ->selectSub(
                    Submission::query()
                        ->join('positions', 'positions.id', '=', 'submissions.position_id')
                        ->whereColumn('positions.campaign_id', 'campaigns.id')
                        ->whereNotNull('submissions.submitted_at')
                        ->selectRaw('count(*)'),
                    'submitted_submissions_count',
                )
                ->selectSub(
                    Submission::query()
                        ->join('positions', 'positions.id', '=', 'submissions.position_id')
                        ->whereColumn('positions.campaign_id', 'campaigns.id')
                        ->whereNotNull('submissions.normalized_score')
                        ->selectRaw('round(avg(submissions.normalized_score), 1)'),
                    'average_score',
                )
                ->orderByDesc('submitted_submissions_count')
                ->limit(5))
            ->columns([
                TextColumn::make('title')
                    ->label('Campagne')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('positions_count')
                    ->label('Postes')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('invitation_tokens_count')
                    ->label('Invitations')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('submitted_submissions_count')
                    ->label('Candidatures')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('average_score')
                    ->label('Score moyen')
                    ->suffix('/100')
                    ->placeholder('—')
                    ->alignEnd(),
            ]);
    }
}
