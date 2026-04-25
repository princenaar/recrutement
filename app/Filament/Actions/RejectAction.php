<?php

namespace App\Filament\Actions;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class RejectAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'reject';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Rejeter')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->modalHeading('Rejeter cette candidature')
            ->visible(fn (Submission $record) => $record->status !== SubmissionStatus::Rejected)
            ->schema([
                Textarea::make('rejection_note')
                    ->label('Note interne (non visible par l\'agent)')
                    ->rows(4),
            ])
            ->action(function (array $data, Submission $record): void {
                $record->update([
                    'status' => SubmissionStatus::Rejected,
                    'rejection_note' => $data['rejection_note'] ?? null,
                ]);

                Notification::make()->title('Candidature rejetée')->warning()->send();
            });
    }
}
