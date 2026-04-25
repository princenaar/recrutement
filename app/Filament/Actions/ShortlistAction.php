<?php

namespace App\Filament\Actions;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ShortlistAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'shortlist';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Présélectionner')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Présélectionner ce candidat ?')
            ->modalSubmitActionLabel('Présélectionner')
            ->visible(fn (Submission $record) => $record->status !== SubmissionStatus::Shortlisted)
            ->action(function (Submission $record): void {
                $record->update([
                    'status' => SubmissionStatus::Shortlisted,
                    'shortlisted_at' => now(),
                    'shortlisted_by' => auth()->id(),
                ]);

                Notification::make()->title('Candidat présélectionné')->success()->send();
            });
    }
}
