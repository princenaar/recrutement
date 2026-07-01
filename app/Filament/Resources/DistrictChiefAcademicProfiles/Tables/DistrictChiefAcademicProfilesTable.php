<?php

namespace App\Filament\Resources\DistrictChiefAcademicProfiles\Tables;

use App\Filament\Exports\DistrictChiefAcademicProfileExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DistrictChiefAcademicProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->label('Prénom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service_start_date')
                    ->label('Prise de service')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('diplomas_count')
                    ->label('Diplômes')
                    ->counts('diplomas')
                    ->sortable(),
                TextColumn::make('training_certificate_path')
                    ->label('Certificat')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Reçu' : 'Non fourni')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->label('Voir'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exporter')
                    ->exporter(DistrictChiefAcademicProfileExporter::class)
                    ->columnMappingColumns(2),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exporter la sélection')
                        ->exporter(DistrictChiefAcademicProfileExporter::class)
                        ->columnMappingColumns(2),
                ])->label('Actions groupées'),
            ]);
    }
}
