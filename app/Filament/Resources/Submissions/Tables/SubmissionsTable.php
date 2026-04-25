<?php

namespace App\Filament\Resources\Submissions\Tables;

use App\Enums\SubmissionStatus;
use App\Models\Campaign;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('agent.matricule')
                    ->label('Matricule')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('agent.last_name')
                    ->label('Candidat')
                    ->formatStateUsing(fn ($record) => $record->agent?->full_name)
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('position.title')
                    ->label('Poste')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position.campaign.title')
                    ->label('Campagne')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('current_structure')
                    ->label('Structure actuelle')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('submitted_at')
                    ->label('Soumise le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('last_updated_at')
                    ->label('Dernière modification')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->searchable(),
                IconColumn::make('cv_path')
                    ->label('CV')
                    ->boolean()
                    ->state(fn ($record) => $record->cv_path !== null),
                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Mise à jour le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(SubmissionStatus::class),
                SelectFilter::make('position_id')
                    ->label('Poste')
                    ->relationship('position', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('campaign_id')
                    ->label('Campagne')
                    ->options(fn () => Campaign::query()
                        ->orderByDesc('id')
                        ->pluck('title', 'id')
                        ->all())
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('position', fn ($positionQuery) => $positionQuery->where('campaign_id', $data['value']))
                        : $query),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Voir'),
            ]);
    }
}
