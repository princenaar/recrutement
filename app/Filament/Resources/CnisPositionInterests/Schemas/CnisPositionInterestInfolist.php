<?php

namespace App\Filament\Resources\CnisPositionInterests\Schemas;

use App\Support\CnisPositions;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CnisPositionInterestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Réponse CNDIS')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Candidat'),
                        TextEntry::make('created_at')
                            ->label('Soumis le')
                            ->dateTime('d/m/Y H:i'),
                        IconEntry::make('not_interested')
                            ->label('Pas intéressé')
                            ->boolean()
                            ->visible(fn ($record): bool => (bool) $record->not_interested),
                        TextEntry::make('interest_status_label')
                            ->label('Statut')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'Pas intéressé' ? 'gray' : 'success'),
                    ]),

                Section::make('Classement des postes')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('first_choice')
                            ->label('Choix 1')
                            ->state(fn ($record): ?string => CnisPositions::title($record->first_choice))
                            ->placeholder('—'),
                        TextEntry::make('second_choice')
                            ->label('Choix 2')
                            ->state(fn ($record): ?string => CnisPositions::title($record->second_choice))
                            ->placeholder('—'),
                        TextEntry::make('third_choice')
                            ->label('Choix 3')
                            ->state(fn ($record): ?string => CnisPositions::title($record->third_choice))
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
