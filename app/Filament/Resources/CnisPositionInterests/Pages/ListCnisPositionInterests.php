<?php

namespace App\Filament\Resources\CnisPositionInterests\Pages;

use App\Filament\Resources\CnisPositionInterests\CnisPositionInterestResource;
use Filament\Resources\Pages\ListRecords;

class ListCnisPositionInterests extends ListRecords
{
    protected static string $resource = CnisPositionInterestResource::class;

    protected static ?string $title = 'Choix des postes CNIS';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
