<?php

namespace App\Filament\Exports;

use App\Models\DistrictChiefAcademicProfile;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class DistrictChiefAcademicProfileExporter extends Exporter
{
    protected static ?string $model = DistrictChiefAcademicProfile::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('first_name')
                ->label('Prénom'),
            ExportColumn::make('last_name')
                ->label('Nom'),
            ExportColumn::make('service_start_date')
                ->label('Date de prise de service')
                ->formatStateUsing(fn ($state): ?string => $state?->format('Y-m-d')),
            ExportColumn::make('diplomas_count')
                ->label('Nombre diplômes')
                ->counts('diplomas'),
            ExportColumn::make('diplomas_detail')
                ->label('Détail diplômes')
                ->state(fn (DistrictChiefAcademicProfile $record): ?string => self::diplomasDetail($record)),
            ExportColumn::make('training_certificate_present')
                ->label('Certificat inscription')
                ->state(fn (DistrictChiefAcademicProfile $record): string => $record->training_certificate_path === null ? 'Non' : 'Oui'),
            ExportColumn::make('created_at')
                ->label('Soumis le')
                ->formatStateUsing(fn ($state): ?string => $state?->format('Y-m-d H:i')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "L'export des informations académiques est terminé : ".Number::format($export->successful_rows).' '.str('ligne')->plural($export->successful_rows).' exportée(s).';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('ligne')->plural($failedRowsCount).' en échec.';
        }

        return $body;
    }

    private static function diplomasDetail(DistrictChiefAcademicProfile $record): ?string
    {
        $formatted = $record->diplomas
            ->map(fn ($diploma): string => collect([
                $diploma->name,
                $diploma->obtained_year,
                $diploma->scan_path,
            ])->filter(fn ($value): bool => filled($value))->implode(' - '))
            ->filter()
            ->implode(' | ');

        return $formatted !== '' ? $formatted : null;
    }
}
