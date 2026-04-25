<?php

namespace App\Filament\Resources\Submissions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invitationToken.id')
                    ->searchable(),
                TextColumn::make('agent.id')
                    ->searchable(),
                TextColumn::make('position.title')
                    ->searchable(),
                TextColumn::make('current_structure')
                    ->searchable(),
                TextColumn::make('current_service')
                    ->searchable(),
                TextColumn::make('service_entry_date')
                    ->label('Date d\'entrée')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('cv_path')
                    ->searchable(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_updated_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('shortlisted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('shortlisted_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
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
