<?php

namespace App\Filament\Resources\CnisPositionInterests\Tables;

use App\Filament\Exports\CnisPositionInterestExporter;
use App\Support\CnisPositions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CnisPositionInterestsTable
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
                TextColumn::make('not_interested')
                    ->label('Statut')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Pas intéressé' : 'Intéressé')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'gray' : 'success')
                    ->sortable(),
                TextColumn::make('first_choice')
                    ->label('Choix 1')
                    ->formatStateUsing(fn (?string $state): ?string => CnisPositions::title($state))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('second_choice')
                    ->label('Choix 2')
                    ->formatStateUsing(fn (?string $state): ?string => CnisPositions::title($state))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('third_choice')
                    ->label('Choix 3')
                    ->formatStateUsing(fn (?string $state): ?string => CnisPositions::title($state))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('interest')
                    ->label('Intérêt')
                    ->options([
                        'interested' => 'Intéressés',
                        'not_interested' => 'Pas intéressés',
                    ])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'interested' => $query->where('not_interested', false),
                        'not_interested' => $query->where('not_interested', true),
                        default => $query,
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Voir'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exporter')
                    ->exporter(CnisPositionInterestExporter::class)
                    ->columnMappingColumns(2),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exporter la sélection')
                        ->exporter(CnisPositionInterestExporter::class)
                        ->columnMappingColumns(2),
                ])->label('Actions groupées'),
            ]);
    }
}
