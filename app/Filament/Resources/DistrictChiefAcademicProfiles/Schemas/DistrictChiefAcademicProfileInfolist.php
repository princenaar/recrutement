<?php

namespace App\Filament\Resources\DistrictChiefAcademicProfiles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DistrictChiefAcademicProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Médecin chef de district')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Nom complet'),
                        TextEntry::make('service_start_date')
                            ->label('Date de prise de service')
                            ->date('d/m/Y'),
                        TextEntry::make('created_at')
                            ->label('Soumis le')
                            ->dateTime('d/m/Y H:i'),
                        IconEntry::make('has_training_certificate')
                            ->label('Certificat d’inscription')
                            ->boolean()
                            ->state(fn ($record): bool => $record->has_training_certificate),
                    ]),

                Section::make('Formation en cours')
                    ->columns(1)
                    ->visible(fn ($record): bool => filled($record->training_certificate_path))
                    ->schema([
                        TextEntry::make('training_certificate_path')
                            ->label('Certificat d’inscription')
                            ->formatStateUsing(fn (): string => 'Télécharger le certificat')
                            ->url(fn ($record): string => route('admin.files.district-chief-academic-profile.certificate', ['profile' => $record->id]))
                            ->openUrlInNewTab(),
                    ]),

                Section::make('Diplômes obtenus')
                    ->schema([
                        RepeatableEntry::make('diplomas')
                            ->label('Diplômes')
                            ->table([
                                TableColumn::make('Nom'),
                                TableColumn::make('Année'),
                                TableColumn::make('Scan'),
                            ])
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nom'),
                                TextEntry::make('obtained_year')
                                    ->label('Année'),
                                TextEntry::make('scan_path')
                                    ->label('Scan')
                                    ->formatStateUsing(fn (): string => 'Télécharger')
                                    ->url(fn ($record): string => route('admin.files.district-chief-diploma.scan', ['diploma' => $record->id]))
                                    ->openUrlInNewTab(),
                            ])
                            ->placeholder('Aucun diplôme.'),
                    ]),
            ]);
    }
}
