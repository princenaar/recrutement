<?php

namespace App\Filament\Exports;

use App\Models\CnisPositionInterest;
use App\Support\CnisPositions;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class CnisPositionInterestExporter extends Exporter
{
    protected static ?string $model = CnisPositionInterest::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('first_name')
                ->label('Prénom'),
            ExportColumn::make('last_name')
                ->label('Nom'),
            ExportColumn::make('interest_status')
                ->label('Statut')
                ->state(fn (CnisPositionInterest $record): string => $record->interest_status_label),
            ExportColumn::make('first_choice')
                ->label('Choix 1')
                ->formatStateUsing(fn (?string $state): ?string => CnisPositions::title($state)),
            ExportColumn::make('second_choice')
                ->label('Choix 2')
                ->formatStateUsing(fn (?string $state): ?string => CnisPositions::title($state)),
            ExportColumn::make('third_choice')
                ->label('Choix 3')
                ->formatStateUsing(fn (?string $state): ?string => CnisPositions::title($state)),
            ExportColumn::make('created_at')
                ->label('Soumis le')
                ->formatStateUsing(fn ($state): ?string => $state?->format('Y-m-d H:i')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "L'export des choix CNIS est terminé : ".Number::format($export->successful_rows).' '.str('ligne')->plural($export->successful_rows).' exportée(s).';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('ligne')->plural($failedRowsCount).' en échec.';
        }

        return $body;
    }
}
