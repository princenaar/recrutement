<?php

namespace App\Filament\Actions;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Services\InvitationService;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class SendBatchInvitationsAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'sendBatchInvitations';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Inviter par email')
            ->icon('heroicon-o-envelope')
            ->modalHeading('Inviter les agents sélectionnés par email')
            ->modalDescription('Seuls les agents sélectionnés avec une adresse email seront invités. Les invitations actives existantes seront conservées.')
            ->modalSubmitActionLabel('Envoyer les invitations')
            ->schema([
                Select::make('campaign_id')
                    ->label('Campagne')
                    ->helperText('L\'agent choisira lui-même le poste parmi ceux de la campagne.')
                    ->options(fn () => Campaign::query()
                        ->where('status', CampaignStatus::Active->value)
                        ->orderByDesc('id')
                        ->pluck('title', 'id')
                        ->all())
                    ->required(),
            ])
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (array $data, Collection $records): void {
                $campaign = Campaign::findOrFail($data['campaign_id']);

                $result = app(InvitationService::class)->sendEmailBatch($records, $campaign);

                $notification = Notification::make()
                    ->title('Envoi groupé terminé')
                    ->body(sprintf(
                        '%d envoyé(s) · %d sans email · %d déjà invité(s) · %d échec(s)',
                        $result->sent,
                        $result->skippedNoEmail,
                        $result->skippedActiveInvitation,
                        $result->failed,
                    ))
                    ->persistent();

                if ($result->failed > 0) {
                    $notification->danger()->send();

                    return;
                }

                if ($result->sent === 0) {
                    $notification->warning()->send();

                    return;
                }

                $notification->success()->send();
            });
    }
}
