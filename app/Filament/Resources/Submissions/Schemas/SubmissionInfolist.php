<?php

namespace App\Filament\Resources\Submissions\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Candidat (iHRIS)')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('agent.matricule')->label('Matricule'),
                        TextEntry::make('agent.full_name')->label('Nom complet'),
                        TextEntry::make('agent.gender')->label('Genre'),
                        TextEntry::make('agent.birth_date')->label('Date de naissance')->date('d/m/Y'),
                        TextEntry::make('agent.email')->label('Email')->placeholder('—'),
                        TextEntry::make('agent.phone')->label('Téléphone')->placeholder('—'),
                        TextEntry::make('agent.category')->label('Catégorie'),
                        TextEntry::make('agent.agent_status')->label('Statut'),
                        TextEntry::make('agent.structure')->label('Structure (iHRIS)'),
                        TextEntry::make('agent.region')->label('Région'),
                    ]),

                Section::make('Poste / Campagne')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('position.title')->label('Poste'),
                        TextEntry::make('position.campaign.title')->label('Campagne'),
                    ]),

                Section::make('Dossier soumis')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')->label('Statut')->badge(),
                        TextEntry::make('submitted_at')->label('Soumis le')->dateTime('d/m/Y H:i'),
                        TextEntry::make('last_updated_at')->label('Dernière modification')->dateTime('d/m/Y H:i'),
                        TextEntry::make('current_structure')->label('Structure actuelle'),
                        TextEntry::make('current_service')->label('Service actuel'),
                        TextEntry::make('service_entry_date')
                            ->label('Date d\'entrée dans le système de santé')
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('seniority_years')
                            ->label('Ancienneté (années)')
                            ->state(fn ($record) => $record->seniority_years)
                            ->placeholder('—'),
                        TextEntry::make('motivation_note')
                            ->label('Note de motivation')
                            ->columnSpanFull()
                            ->placeholder('—'),
                        IconEntry::make('cv_path')
                            ->label('CV téléversé')
                            ->boolean()
                            ->state(fn ($record) => $record->cv_path !== null),
                    ]),

                Section::make('Diplômes')
                    ->schema([
                        TextEntry::make('diplomas')
                            ->label('')
                            ->state(fn ($record) => $record->diplomas
                                ->map(fn ($d) => trim(sprintf(
                                    '%s — %s%s',
                                    $d->title,
                                    $d->institution ?? '',
                                    $d->year ? " ({$d->year})" : '',
                                )))
                                ->implode("\n"))
                            ->placeholder('Aucun diplôme.'),
                    ]),

                Section::make('Décision')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('shortlistedBy.name')->label('Présélectionné par')->placeholder('—'),
                        TextEntry::make('shortlisted_at')->label('Date présélection')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('rejection_note')
                            ->label('Note de rejet')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
