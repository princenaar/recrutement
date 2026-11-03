<?php

namespace App\Filament\Resources\CommunicationApplications\Tables;

use App\Support\CommunicationCall;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommunicationApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Candidat')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name', 'first_name']),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('position')
                    ->label('Poste')
                    ->formatStateUsing(fn (string $state): string => CommunicationCall::positionOptions()[$state] ?? $state)
                    ->wrap(),
                TextColumn::make('current_structure')
                    ->label('Structure d’affectation')
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Déposée le')
                    ->dateTime('d/m/Y H:i', 'Africa/Dakar')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('position')
                    ->label('Poste')
                    ->options(CommunicationCall::positionOptions()),
            ])
            ->recordActions([
                ViewAction::make()->label('Voir le dossier'),
            ]);
    }
}
