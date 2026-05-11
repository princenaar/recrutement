<?php

namespace App\Filament\Resources\Agents\Tables;

use App\Filament\Actions\ImportAgentsAction;
use App\Filament\Actions\SendBatchInvitationsAction;
use App\Filament\Actions\SendInvitationAction;
use App\Filament\Exports\AgentExporter;
use App\Models\Agent;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AgentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('matricule')
                    ->label('Matricule')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('import_name')
                    ->label('Import')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('first_name')
                    ->label('Prénom')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('structure')
                    ->label('Structure')
                    ->searchable(),
                TextColumn::make('region')
                    ->label('Région')
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Catégorie')
                    ->badge(),
                TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('—')
                    ->searchable(),
                IconColumn::make('has_active_invitation')
                    ->label('Invité')
                    ->state(fn (Agent $record) => $record->invitationTokens()
                        ->whereNull('revoked_at')
                        ->where('expires_at', '>', now())
                        ->exists())
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('region')
                    ->label('Région')
                    ->options(fn () => Agent::query()
                        ->whereNotNull('region')
                        ->distinct()
                        ->pluck('region', 'region')
                        ->all()),
                SelectFilter::make('structure')
                    ->label('Structure')
                    ->options(fn () => Agent::query()
                        ->whereNotNull('structure')
                        ->distinct()
                        ->pluck('structure', 'structure')
                        ->all()),
                SelectFilter::make('import_name')
                    ->label('Import')
                    ->options(fn () => Agent::query()
                        ->whereNotNull('import_name')
                        ->distinct()
                        ->orderBy('import_name')
                        ->pluck('import_name', 'import_name')
                        ->all()),
                SelectFilter::make('submitted_submission')
                    ->label('Candidature soumise')
                    ->options([
                        'with' => 'Avec candidature soumise',
                        'without' => 'Sans candidature soumise',
                    ])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'with' => $query->whereHas('submissions', fn ($submissionQuery) => $submissionQuery->whereNotNull('submitted_at')),
                        'without' => $query->whereDoesntHave('submissions', fn ($submissionQuery) => $submissionQuery->whereNotNull('submitted_at')),
                        default => $query,
                    }),
                SelectFilter::make('active_invitation')
                    ->label('Invitation active')
                    ->options([
                        'with' => 'Avec invitation active',
                        'without' => 'Sans invitation active',
                    ])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'with' => $query->whereHas('invitationTokens', fn ($invitationQuery) => $invitationQuery
                            ->whereNull('revoked_at')
                            ->where('expires_at', '>', now())),
                        'without' => $query->whereDoesntHave('invitationTokens', fn ($invitationQuery) => $invitationQuery
                            ->whereNull('revoked_at')
                            ->where('expires_at', '>', now())),
                        default => $query,
                    }),
                SelectFilter::make('has_email')
                    ->label('Email')
                    ->options([
                        'with' => 'Avec email',
                        'without' => 'Sans email',
                    ])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'with' => $query->whereNotNull('email'),
                        'without' => $query->whereNull('email'),
                        default => $query,
                    }),
            ])
            ->headerActions([
                ImportAgentsAction::make(),
                ExportAction::make()
                    ->label('Exporter')
                    ->exporter(AgentExporter::class)
                    ->columnMappingColumns(3),
            ])
            ->recordActions([
                SendInvitationAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    SendBatchInvitationsAction::make(),
                    ExportBulkAction::make()
                        ->label('Exporter la sélection')
                        ->exporter(AgentExporter::class)
                        ->columnMappingColumns(3),
                ])->label('Actions groupées'),
            ]);
    }
}
