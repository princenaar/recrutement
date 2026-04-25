<?php

namespace App\Filament\Actions;

use App\Services\AgentImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ImportAgentsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'importAgents';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Importer iHRIS (Excel)')
            ->icon('heroicon-o-arrow-up-tray')
            ->modalHeading('Importer les agents depuis iHRIS')
            ->modalDescription('Sélectionnez l\'export iHRIS au format .xlsx. Les agents existants sont mis à jour par matricule.')
            ->schema([
                FileUpload::make('file')
                    ->label('Fichier Excel (.xlsx)')
                    ->disk('local')
                    ->directory('imports')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->required(),
            ])
            ->action(function (array $data): void {
                $relativePath = $data['file'];
                $absolutePath = Storage::disk('local')->path($relativePath);

                $result = app(AgentImportService::class)->import($absolutePath);

                Storage::disk('local')->delete($relativePath);

                Notification::make()
                    ->title('Import terminé')
                    ->body(sprintf(
                        '%d créé(s) · %d mis à jour · %d ignoré(s)',
                        $result->created,
                        $result->updated,
                        $result->skipped,
                    ))
                    ->success()
                    ->send();
            });
    }
}
