<?php

namespace App\Filament\Exports;

use App\Models\Agent;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use UnitEnum;

class AgentExporter extends Exporter
{
    protected static ?string $model = Agent::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('matricule')
                ->label('Matricule'),
            ExportColumn::make('first_name')
                ->label('Prénom'),
            ExportColumn::make('last_name')
                ->label('Nom'),
            ExportColumn::make('full_name')
                ->label('Nom complet')
                ->state(fn (Agent $record): string => $record->full_name),
            ExportColumn::make('gender')
                ->label('Genre'),
            ExportColumn::make('birth_date')
                ->label('Date de naissance')
                ->formatStateUsing(fn ($state): ?string => self::formatDate($state)),
            ExportColumn::make('nationality')
                ->label('Nationalité'),
            ExportColumn::make('email')
                ->label('Email'),
            ExportColumn::make('phone')
                ->label('Téléphone'),
            ExportColumn::make('category')
                ->label('Catégorie / Diplôme'),
            ExportColumn::make('current_position')
                ->label('Fonction actuelle / Niveau diplôme'),
            ExportColumn::make('position_start_date')
                ->label('Date occupation poste')
                ->formatStateUsing(fn ($state): ?string => self::formatDate($state)),
            ExportColumn::make('service')
                ->label('Service'),
            ExportColumn::make('structure')
                ->label('Structure'),
            ExportColumn::make('district')
                ->label('District / Hôpital'),
            ExportColumn::make('region')
                ->label('Région'),
            ExportColumn::make('employer')
                ->label('Employeur'),
            ExportColumn::make('contract_type')
                ->label('Type de contrat'),
            ExportColumn::make('agent_status')
                ->label('Statut agent'),
            ExportColumn::make('entry_date')
                ->label('Date entrée système santé')
                ->formatStateUsing(fn ($state): ?string => self::formatDate($state)),
            ExportColumn::make('marital_status')
                ->label('Situation matrimoniale'),
            ExportColumn::make('import_source')
                ->label('Source import')
                ->enabledByDefault(false),
            ExportColumn::make('import_name')
                ->label('Nom import'),
            ExportColumn::make('ihris_imported_at')
                ->label('Importé le')
                ->formatStateUsing(fn ($state): ?string => self::formatDateTime($state))
                ->enabledByDefault(false),
            ExportColumn::make('source_payload')
                ->label('Données source import')
                ->formatStateUsing(fn ($state): ?string => self::formatJson($state))
                ->enabledByDefault(false),
            ExportColumn::make('invitation_tokens_count')
                ->label('Nombre invitations')
                ->counts('invitationTokens'),
            ExportColumn::make('active_invitations_count')
                ->label('Invitations actives')
                ->state(fn (Agent $record): int => $record->invitationTokens
                    ->filter(fn ($token): bool => $token->isActive())
                    ->count()),
            ExportColumn::make('invited_campaigns')
                ->label('Campagnes invitées')
                ->state(fn (Agent $record): ?string => self::joinDistinct(
                    $record->invitationTokens->pluck('campaign.title')->all()
                ))
                ->enabledByDefault(false),
            ExportColumn::make('invitation_channels')
                ->label('Canaux invitation')
                ->state(fn (Agent $record): ?string => self::joinDistinct(
                    $record->invitationTokens
                        ->map(fn ($token) => self::enumLabel($token->notification_channel))
                        ->all()
                ))
                ->enabledByDefault(false),
            ExportColumn::make('invitation_statuses')
                ->label('Statuts invitation')
                ->state(fn (Agent $record): ?string => self::joinDistinct(
                    $record->invitationTokens
                        ->map(fn ($token): string => match (true) {
                            $token->isRevoked() => 'Révoquée',
                            $token->isExpired() => 'Expirée',
                            default => 'Active',
                        })
                        ->all()
                )),
            ExportColumn::make('invitations_detail')
                ->label('Détail invitations')
                ->state(fn (Agent $record): ?string => self::invitationsDetail($record))
                ->enabledByDefault(false),
            ExportColumn::make('submissions_count')
                ->label('Nombre candidatures')
                ->counts('submissions'),
            ExportColumn::make('submission_campaigns')
                ->label('Campagnes candidatures')
                ->state(fn (Agent $record): ?string => self::joinDistinct(
                    $record->submissions->pluck('position.campaign.title')->all()
                ))
                ->enabledByDefault(false),
            ExportColumn::make('submission_positions')
                ->label('Postes candidatés')
                ->state(fn (Agent $record): ?string => self::joinDistinct(
                    $record->submissions->pluck('position.title')->all()
                )),
            ExportColumn::make('submission_statuses')
                ->label('Statuts candidatures')
                ->state(fn (Agent $record): ?string => self::joinDistinct(
                    $record->submissions
                        ->map(fn ($submission) => self::enumLabel($submission->status))
                        ->all()
                )),
            ExportColumn::make('submitted_at_values')
                ->label('Dates soumission')
                ->state(fn (Agent $record): ?string => self::joinDistinct(
                    $record->submissions
                        ->map(fn ($submission): ?string => self::formatDateTime($submission->submitted_at))
                        ->all()
                ))
                ->enabledByDefault(false),
            ExportColumn::make('normalized_scores')
                ->label('Scores /100')
                ->state(fn (Agent $record): ?string => self::joinDistinct(
                    $record->submissions
                        ->map(fn ($submission): ?string => $submission->normalized_score === null ? null : (string) $submission->normalized_score)
                        ->all()
                ))
                ->enabledByDefault(false),
            ExportColumn::make('region_choices')
                ->label('Régions choisies')
                ->state(fn (Agent $record): ?string => self::joinDistinct(
                    $record->submissions
                        ->flatMap(fn ($submission): array => is_array($submission->region_choices) ? $submission->region_choices : [])
                        ->all()
                ))
                ->enabledByDefault(false),
            ExportColumn::make('submissions_detail')
                ->label('Détail candidatures')
                ->state(fn (Agent $record): ?string => self::submissionsDetail($record))
                ->enabledByDefault(false),
            ExportColumn::make('created_at')
                ->label('Créé le')
                ->formatStateUsing(fn ($state): ?string => self::formatDateTime($state))
                ->enabledByDefault(false),
            ExportColumn::make('updated_at')
                ->label('Mis à jour le')
                ->formatStateUsing(fn ($state): ?string => self::formatDateTime($state))
                ->enabledByDefault(false),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'invitationTokens.campaign',
            'submissions.position.campaign',
        ]);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "L'export des candidats est terminé : ".Number::format($export->successful_rows).' '.str('ligne')->plural($export->successful_rows).' exportée(s).';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('ligne')->plural($failedRowsCount).' en échec.';
        }

        return $body;
    }

    private static function formatDate(mixed $state): ?string
    {
        return $state?->format('Y-m-d');
    }

    private static function formatDateTime(mixed $state): ?string
    {
        return $state?->format('Y-m-d H:i');
    }

    private static function formatJson(mixed $state): ?string
    {
        if ($state === null || $state === []) {
            return null;
        }

        return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function enumLabel(mixed $state): ?string
    {
        if ($state instanceof UnitEnum && method_exists($state, 'getLabel')) {
            return $state->getLabel();
        }

        return $state === null ? null : (string) $state;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private static function joinDistinct(array $values): ?string
    {
        $joined = collect($values)
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): string => (string) $value)
            ->unique()
            ->implode(' | ');

        return $joined === '' ? null : $joined;
    }

    private static function invitationsDetail(Agent $record): ?string
    {
        return self::joinDistinct($record->invitationTokens
            ->map(fn ($token): string => collect([
                $token->campaign?->title,
                self::enumLabel($token->notification_channel),
                match (true) {
                    $token->isRevoked() => 'Révoquée',
                    $token->isExpired() => 'Expirée',
                    default => 'Active',
                },
                'expire le '.self::formatDateTime($token->expires_at),
            ])->filter()->implode(' - '))
            ->all());
    }

    private static function submissionsDetail(Agent $record): ?string
    {
        return self::joinDistinct($record->submissions
            ->map(fn ($submission): string => collect([
                $submission->position?->campaign?->title,
                $submission->position?->title,
                self::enumLabel($submission->status),
                self::formatDateTime($submission->submitted_at),
                $submission->normalized_score === null ? null : $submission->normalized_score.'/100',
                self::joinDistinct(is_array($submission->region_choices) ? $submission->region_choices : []),
            ])->filter()->implode(' - '))
            ->all());
    }
}
