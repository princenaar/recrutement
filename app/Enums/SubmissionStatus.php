<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SubmissionStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Submitted => 'Soumise',
            self::UnderReview => 'En revue',
            self::Shortlisted => 'Présélectionnée',
            self::Rejected => 'Rejetée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'info',
            self::UnderReview => 'warning',
            self::Shortlisted => 'success',
            self::Rejected => 'danger',
        };
    }
}
