<?php

namespace App\Filament\Resources\CnisPositionInterests\Pages;

use App\Filament\Resources\CnisPositionInterests\CnisPositionInterestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCnisPositionInterest extends ViewRecord
{
    protected static string $resource = CnisPositionInterestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
