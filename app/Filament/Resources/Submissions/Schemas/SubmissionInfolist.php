<?php

namespace App\Filament\Resources\Submissions\Schemas;

use App\Enums\CampaignFormType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Candidat')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('agent.matricule')->label('Matricule'),
                        TextEntry::make('agent.full_name')->label('Nom complet'),
                        TextEntry::make('agent.gender')->label('Genre'),
                        TextEntry::make('agent.birth_date')->label('Date de naissance')->date('d/m/Y'),
                        TextEntry::make('agent.email')->label('Email')->placeholder('—'),
                        TextEntry::make('agent.phone')->label('Téléphone')->placeholder('—'),
                        TextEntry::make('agent.category')->label('Catégorie')->badge(),
                        TextEntry::make('agent.agent_status')->label('Statut candidat')->badge(),
                        TextEntry::make('agent.contract_type')->label('Type de contrat')->placeholder('—'),
                        TextEntry::make('agent.current_position')->label('Fonction actuelle')->placeholder('—'),
                        TextEntry::make('agent.structure')->label('Structure de référence'),
                        TextEntry::make('agent.service')->label('Service de référence')->placeholder('—'),
                        TextEntry::make('agent.region')->label('Région'),
                    ]),

                Section::make('Poste / Campagne')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('position.title')->label('Poste'),
                        TextEntry::make('position.campaign.title')->label('Campagne'),
                        TextEntry::make('position.status')->label('Statut du poste')->badge(),
                        TextEntry::make('position.campaign.status')->label('Statut de la campagne')->badge(),
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
                    ]),

                Section::make('Questionnaire à critères')
                    ->columns(2)
                    ->visible(fn ($record) => $record->position?->campaign?->form_type === CampaignFormType::CriteriaQuestionnaire)
                    ->schema([
                        IconEntry::make('responses.currently_active')
                            ->label('En activité')
                            ->boolean()
                            ->state(fn ($record) => ($record->responses['currently_active'] ?? null) === 'yes'),
                        TextEntry::make('responses.activity_location')
                            ->label('Lieu d’activité')
                            ->placeholder('—'),
                        TextEntry::make('region_choices')
                            ->label('Régions choisies')
                            ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : null)
                            ->placeholder('—'),
                        TextEntry::make('raw_score')
                            ->label('Score brut')
                            ->suffix('/65')
                            ->placeholder('—'),
                        TextEntry::make('normalized_score')
                            ->label('Score normalisé')
                            ->suffix('/100')
                            ->placeholder('—'),
                        TextEntry::make('score_breakdown')
                            ->label('Détail des points')
                            ->formatStateUsing(fn ($state) => collect($state ?? [])
                                ->map(fn ($points, $criterion) => "{$criterion} : {$points} pt")
                                ->implode("\n"))
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),

                Section::make('Documents')
                    ->visible(fn ($record) => $record->position?->campaign?->form_type !== CampaignFormType::CriteriaQuestionnaire)
                    ->columns(2)
                    ->schema([
                        IconEntry::make('cv_path')
                            ->label('CV reçu')
                            ->boolean()
                            ->state(fn ($record) => $record->cv_path !== null),
                        TextEntry::make('cv_path')
                            ->label('CV')
                            ->formatStateUsing(fn () => 'Télécharger le CV')
                            ->url(fn ($record) => $record->cv_path !== null
                                ? route('admin.files.cv', ['submission' => $record->id])
                                : null)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => $record->cv_path !== null),
                        RepeatableEntry::make('diplomas')
                            ->label('Diplômes')
                            ->columnSpanFull()
                            ->table([
                                TableColumn::make('Titre'),
                                TableColumn::make('Établissement'),
                                TableColumn::make('Année'),
                                TableColumn::make('Fichier'),
                            ])
                            ->schema([
                                TextEntry::make('title')->label('Titre'),
                                TextEntry::make('institution')->label('Établissement')->placeholder('—'),
                                TextEntry::make('year')->label('Année')->placeholder('—'),
                                TextEntry::make('file_path')
                                    ->label('Fichier')
                                    ->formatStateUsing(fn () => 'Télécharger')
                                    ->url(fn ($record) => route('admin.files.diploma', ['diploma' => $record->id]))
                                    ->openUrlInNewTab(),
                            ])
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
