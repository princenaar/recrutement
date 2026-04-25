<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvitationChannel: string implements HasColor, HasLabel
{
    case Email = 'email';
    case Manual = 'manual';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Manual => 'Manuel',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Email => 'info',
            self::Manual => 'warning',
        };
    }
}
