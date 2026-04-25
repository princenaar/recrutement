<?php

namespace App\Filament\Resources\Campaigns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Campagne')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('positions_count')
                    ->label('Postes')
                    ->counts('positions')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
