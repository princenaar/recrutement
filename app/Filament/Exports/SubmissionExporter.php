<?php

namespace App\Filament\Exports;

use App\Models\Submission;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use UnitEnum;

class SubmissionExporter extends Exporter
{
    protected static ?string $model = Submission::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('agent.matricule')
                ->label('Matricule'),
            ExportColumn::make('agent.full_name')
                ->label('Candidat')
                ->state(fn (Submission $record): ?string => $record->agent?->full_name),
            ExportColumn::make('agent.first_name')
                ->label('Prénom')
                ->enabledByDefault(false),
            ExportColumn::make('agent.last_name')
                ->label('Nom')
                ->enabledByDefault(false),
            ExportColumn::make('agent.email')
                ->label('Email'),
            ExportColumn::make('agent.phone')
                ->label('Téléphone')
                ->enabledByDefault(false),
            ExportColumn::make('agent.category')
                ->label('Catégorie / Diplôme'),
            ExportColumn::make('agent.current_position')
                ->label('Fonction actuelle / Niveau diplôme'),
            ExportColumn::make('agent.structure')
                ->label('Structure iHRIS')
                ->enabledByDefault(false),
            ExportColumn::make('agent.service')
                ->label('Service iHRIS')
                ->enabledByDefault(false),
            ExportColumn::make('agent.district')
                ->label('District / Hôpital')
                ->enabledByDefault(false),
            ExportColumn::make('agent.region')
                ->label('Région iHRIS'),
            ExportColumn::make('agent.import_name')
                ->label('Import agent'),
            ExportColumn::make('position.title')
                ->label('Poste'),
            ExportColumn::make('position.campaign.title')
                ->label('Campagne'),
            ExportColumn::make('position.campaign.form_type')
                ->label('Type formulaire')
                ->formatStateUsing(fn ($state): ?string => self::enumLabel($state)),
            ExportColumn::make('position.required_profile')
                ->label('Profil requis')
                ->enabledByDefault(false),
            ExportColumn::make('current_structure')
                ->label('Structure actuelle'),
            ExportColumn::make('current_service')
                ->label('Service actuel'),
            ExportColumn::make('service_entry_date')
                ->label('Date entrée service')
                ->formatStateUsing(fn ($state): ?string => self::formatDate($state)),
            ExportColumn::make('seniority_years')
                ->label('Ancienneté')
                ->state(fn (Submission $record): ?int => $record->seniority_years)
                ->enabledByDefault(false),
            ExportColumn::make('motivation_note')
                ->label('Note motivation')
                ->enabledByDefault(false),
            ExportColumn::make('cv_path')
                ->label('CV'),
            ExportColumn::make('diplomas_count')
                ->label('Nombre diplômes')
                ->counts('diplomas'),
            ExportColumn::make('diplomas_detail')
                ->label('Détail diplômes')
                ->state(fn (Submission $record): ?string => self::diplomasDetail($record))
                ->enabledByDefault(false),
            ExportColumn::make('responses')
                ->label('Réponses questionnaire')
                ->formatStateUsing(fn ($state): ?string => self::formatKeyValueJson($state))
                ->enabledByDefault(false),
            ExportColumn::make('responses.currently_active')
                ->label('En activité')
                ->state(fn (Submission $record): ?string => self::yesNo($record->responses['currently_active'] ?? null)),
            ExportColumn::make('responses.activity_location')
                ->label('Lieu activité')
                ->state(fn (Submission $record): ?string => $record->responses['activity_location'] ?? null),
            ExportColumn::make('responses.degree_level')
                ->label('Niveau diplôme questionnaire')
                ->state(fn (Submission $record): ?string => $record->responses['degree_level'] ?? null)
                ->enabledByDefault(false),
            ExportColumn::make('responses.experience_years')
                ->label('Années expérience')
                ->state(fn (Submission $record): mixed => $record->responses['experience_years'] ?? null),
            ExportColumn::make('responses.knows_snis')
                ->label('Connaissance SNIS')
                ->state(fn (Submission $record): ?string => self::yesNo($record->responses['knows_snis'] ?? null))
                ->enabledByDefault(false),
            ExportColumn::make('responses.dhis2_level')
                ->label('Niveau DHIS2')
                ->state(fn (Submission $record): ?string => $record->responses['dhis2_level'] ?? null)
                ->enabledByDefault(false),
            ExportColumn::make('responses.computer_skills')
                ->label('Maîtrise informatique')
                ->state(fn (Submission $record): ?string => self::yesNo($record->responses['computer_skills'] ?? null))
                ->enabledByDefault(false),
            ExportColumn::make('region_choices')
                ->label('Régions choisies')
                ->formatStateUsing(fn ($state): ?string => self::formatList($state)),
            ExportColumn::make('score_breakdown')
                ->label('Détail des points')
                ->formatStateUsing(fn ($state): ?string => self::formatScoreBreakdown($state))
                ->enabledByDefault(false),
            ExportColumn::make('raw_score')
                ->label('Score brut'),
            ExportColumn::make('normalized_score')
                ->label('Score /100'),
            ExportColumn::make('status')
                ->label('Statut')
                ->formatStateUsing(fn ($state): ?string => self::enumLabel($state)),
            ExportColumn::make('submitted_at')
                ->label('Soumise le')
                ->formatStateUsing(fn ($state): ?string => self::formatDateTime($state)),
            ExportColumn::make('last_updated_at')
                ->label('Dernière modification')
                ->formatStateUsing(fn ($state): ?string => self::formatDateTime($state))
                ->enabledByDefault(false),
            ExportColumn::make('shortlisted_at')
                ->label('Présélectionnée le')
                ->formatStateUsing(fn ($state): ?string => self::formatDateTime($state)),
            ExportColumn::make('shortlistedBy.name')
                ->label('Présélectionnée par')
                ->enabledByDefault(false),
            ExportColumn::make('rejection_note')
                ->label('Note rejet')
                ->enabledByDefault(false),
            ExportColumn::make('invitationToken.token')
                ->label('Token invitation')
                ->enabledByDefault(false),
            ExportColumn::make('invitationToken.notification_channel')
                ->label('Canal invitation')
                ->formatStateUsing(fn ($state): ?string => self::enumLabel($state))
                ->enabledByDefault(false),
            ExportColumn::make('invitationToken.expires_at')
                ->label('Expiration invitation')
                ->formatStateUsing(fn ($state): ?string => self::formatDateTime($state))
                ->enabledByDefault(false),
            ExportColumn::make('invitation_status')
                ->label('Statut invitation')
                ->state(fn (Submission $record): ?string => self::invitationStatus($record)),
            ExportColumn::make('created_at')
                ->label('Créée le')
                ->formatStateUsing(fn ($state): ?string => self::formatDateTime($state))
                ->enabledByDefault(false),
            ExportColumn::make('updated_at')
                ->label('Mise à jour le')
                ->formatStateUsing(fn ($state): ?string => self::formatDateTime($state))
                ->enabledByDefault(false),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'agent',
            'position.campaign',
            'invitationToken',
            'shortlistedBy',
            'diplomas',
        ]);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "L'export des candidatures est terminé : ".Number::format($export->successful_rows).' '.str('ligne')->plural($export->successful_rows).' exportée(s).';

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

    private static function enumLabel(mixed $state): ?string
    {
        if ($state instanceof UnitEnum && method_exists($state, 'getLabel')) {
            return $state->getLabel();
        }

        return $state === null ? null : (string) $state;
    }

    private static function yesNo(mixed $state): ?string
    {
        return match ($state) {
            'yes', true, 1 => 'Oui',
            'no', false, 0 => 'Non',
            default => $state === null ? null : (string) $state,
        };
    }

    private static function formatList(mixed $state): ?string
    {
        if (! is_array($state)) {
            return $state === null ? null : (string) $state;
        }

        $formatted = collect($state)
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): string => (string) $value)
            ->implode(' | ');

        return $formatted === '' ? null : $formatted;
    }

    private static function formatKeyValueJson(mixed $state): ?string
    {
        if (! is_array($state) || $state === []) {
            return null;
        }

        return collect($state)
            ->map(fn ($value, $key): string => self::jsonLabel((string) $key).' : '.self::jsonValue($value))
            ->implode(' | ');
    }

    private static function formatScoreBreakdown(mixed $state): ?string
    {
        if (! is_array($state) || $state === []) {
            return null;
        }

        return collect($state)
            ->map(fn ($points, $criterion): string => self::scoreCriterionLabel((string) $criterion).' : '.$points.' pt')
            ->implode(' | ');
    }

    private static function jsonValue(mixed $value): string
    {
        if (is_array($value)) {
            return self::formatList($value) ?? '';
        }

        if ($value instanceof UnitEnum) {
            return self::enumLabel($value) ?? '';
        }

        return match ($value) {
            true => 'Oui',
            false => 'Non',
            null => '',
            default => (string) $value,
        };
    }

    private static function jsonLabel(string $key): string
    {
        return match ($key) {
            'currently_active' => 'En activité',
            'activity_location' => 'Lieu activité',
            'degree_level' => 'Niveau diplôme',
            'experience_years' => 'Années expérience',
            'knows_snis' => 'Connaissance SNIS',
            'dhis2_level' => 'Niveau DHIS2',
            'computer_skills' => 'Maîtrise informatique',
            'motivation_note' => 'Note motivation',
            default => str($key)->replace('_', ' ')->ucfirst()->toString(),
        };
    }

    private static function scoreCriterionLabel(string $criterion): string
    {
        return match ($criterion) {
            'degree' => 'Diplôme',
            'experience' => 'Expérience',
            'snis' => 'Connaissance SNIS',
            'dhis2' => 'Connaissance DHIS2',
            'computer_skills' => 'Maîtrise informatique',
            'terrain_motivation' => 'Motivation terrain',
            default => str($criterion)->replace('_', ' ')->ucfirst()->toString(),
        };
    }

    private static function invitationStatus(Submission $record): ?string
    {
        $token = $record->invitationToken;

        if ($token === null) {
            return null;
        }

        return match (true) {
            $token->isRevoked() => 'Révoquée',
            $token->isExpired() => 'Expirée',
            default => 'Active',
        };
    }

    private static function diplomasDetail(Submission $record): ?string
    {
        $formatted = $record->diplomas
            ->map(fn ($diploma): string => collect([
                $diploma->title,
                $diploma->institution,
                $diploma->year,
                $diploma->file_path,
            ])->filter()->implode(' - '))
            ->filter()
            ->implode(' | ');

        return $formatted === '' ? null : $formatted;
    }
}
