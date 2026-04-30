<?php

namespace App\Filament\Actions;

use App\Services\AgentImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
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
            ->label('Importer Excel')
            ->icon('heroicon-o-arrow-up-tray')
            ->modalHeading('Importer les candidats depuis Excel')
            ->modalDescription('Sélectionnez l\'export au format .xlsx. Les candidats existants sont mis à jour par identifiant.')
            ->schema([
                TextInput::make('import_name')
                    ->label('Nom de l\'import')
                    ->placeholder('Ex. Gestionnaires des données 2026')
                    ->required()
                    ->maxLength(255),
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
                $importName = trim((string) $data['import_name']);

                $result = app(AgentImportService::class)->import($absolutePath, $importName);

                Storage::disk('local')->delete($relativePath);

                Notification::make()
                    ->title('Import terminé')
                    ->body(sprintf(
                        '%s : %d créé(s) · %d mis à jour · %d ignoré(s)',
                        $importName,
                        $result->created,
                        $result->updated,
                        $result->skipped,
                    ))
                    ->success()
                    ->send();
            });
    }
}
