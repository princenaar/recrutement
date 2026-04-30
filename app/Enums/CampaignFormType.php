<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CampaignFormType: string implements HasLabel
{
    case DocumentDossier = 'document_dossier';
    case CriteriaQuestionnaire = 'criteria_questionnaire';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DocumentDossier => 'Dossier avec documents',
            self::CriteriaQuestionnaire => 'Questionnaire à critères',
        };
    }
}
