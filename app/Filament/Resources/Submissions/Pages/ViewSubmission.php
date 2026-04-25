<?php

namespace App\Filament\Resources\Submissions\Pages;

use App\Filament\Actions\RejectAction;
use App\Filament\Actions\ShortlistAction;
use App\Filament\Resources\Submissions\SubmissionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadCv')
                ->label('Télécharger le CV')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => route('admin.files.cv', ['submission' => $this->record->id]))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->cv_path !== null),

            ShortlistAction::make()->record($this->record),
            RejectAction::make()->record($this->record),
        ];
    }
}
