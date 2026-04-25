<?php

namespace App\Filament\Resources\InvitationTokens\Tables;

use App\Enums\InvitationChannel;
use App\Models\InvitationToken;
use App\Notifications\InvitationNotification;
use App\Services\InvitationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Livewire\Component as Livewire;

class InvitationTokensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('agent.matricule')
                    ->label('Matricule')
                    ->searchable(),
                TextColumn::make('agent.last_name')
                    ->label('Agent')
                    ->formatStateUsing(fn ($record) => trim($record->agent->first_name.' '.$record->agent->last_name))
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('campaign.title')
                    ->label('Campagne')
                    ->searchable(),
                TextColumn::make('expires_at')
                    ->label('Expire le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('notification_channel')
                    ->label('Canal')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('state')
                    ->label('État')
                    ->badge()
                    ->state(fn (InvitationToken $record) => match (true) {
                        $record->isRevoked() => 'révoqué',
                        $record->isExpired() => 'expiré',
                        default => 'actif',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'actif' => 'success',
                        'expiré' => 'warning',
                        'révoqué' => 'danger',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('state')
                    ->label('État')
                    ->options([
                        'active' => 'Actif',
                        'expired' => 'Expiré',
                        'revoked' => 'Révoqué',
                    ])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'active' => $query->whereNull('revoked_at')->where('expires_at', '>', now()),
                        'expired' => $query->whereNull('revoked_at')->where('expires_at', '<=', now()),
                        'revoked' => $query->whereNotNull('revoked_at'),
                        default => $query,
                    }),
                SelectFilter::make('notification_channel')
                    ->label('Canal')
                    ->options(InvitationChannel::class),
            ])
            ->recordActions([
                Action::make('copyInvitation')
                    ->label('Copier l\'invitation')
                    ->icon('heroicon-o-clipboard-document')
                    ->action(function (InvitationToken $record, InvitationService $service, Livewire $livewire): void {
                        $message = $service->buildManualMessage($record);

                        $livewire->dispatch('copy-to-clipboard', text: $message);

                        Notification::make()
                            ->title('Texte d\'invitation copié dans le presse-papier')
                            ->success()
                            ->send();
                    }),

                Action::make('resendEmail')
                    ->label('Renvoyer le mail')
                    ->icon('heroicon-o-envelope')
                    ->visible(fn (InvitationToken $record) => $record->isActive() && $record->agent->email !== null)
                    ->requiresConfirmation()
                    ->action(function (InvitationToken $record): void {
                        $record->loadMissing(['agent', 'campaign']);
                        NotificationFacade::send($record->agent, new InvitationNotification($record));
                        $record->update([
                            'notification_channel' => InvitationChannel::Email,
                            'notification_sent_at' => now(),
                        ]);

                        Notification::make()->title('Mail renvoyé')->success()->send();
                    }),

                Action::make('revoke')
                    ->label('Révoquer')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (InvitationToken $record) => $record->isActive())
                    ->action(function (InvitationToken $record): void {
                        $record->update(['revoked_at' => now()]);
                        Notification::make()->title('Lien révoqué')->success()->send();
                    }),
            ]);
    }
}
