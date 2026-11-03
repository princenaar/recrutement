<?php

namespace App\Filament\Resources\CommunicationApplications\Schemas;

use App\Support\CommunicationCall;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommunicationApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Candidat')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('full_name')->label('Nom complet'),
                        TextEntry::make('email')->label('E-mail'),
                        TextEntry::make('phone')->label('Téléphone'),
                        TextEntry::make('administrative_status')
                            ->label('Statut administratif')
                            ->formatStateUsing(fn (string $state): string => CommunicationCall::administrativeStatuses()[$state] ?? $state),
                        TextEntry::make('current_structure')->label('Structure d’affectation'),
                        TextEntry::make('created_at')->label('Déposée le')->dateTime('d/m/Y H:i', 'Africa/Dakar'),
                    ]),
                Section::make('Candidature')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('position')
                            ->label('Poste')
                            ->formatStateUsing(fn (string $state): string => CommunicationCall::positionOptions()[$state] ?? $state),
                        TextEntry::make('motivation')
                            ->label('Motivation et expériences pertinentes')
                            ->extraAttributes(['class' => 'whitespace-pre-wrap']),
                    ]),
                Section::make('Curriculum vitae')
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('cv_path')
                            ->hiddenLabel()
                            ->view('filament.communication-applications.cv-preview'),
                    ]),
            ]);
    }
}
