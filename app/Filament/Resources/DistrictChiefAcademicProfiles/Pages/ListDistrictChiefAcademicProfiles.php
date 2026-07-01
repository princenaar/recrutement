<?php

namespace App\Filament\Resources\DistrictChiefAcademicProfiles\Pages;

use App\Filament\Resources\DistrictChiefAcademicProfiles\DistrictChiefAcademicProfileResource;
use Filament\Resources\Pages\ListRecords;

class ListDistrictChiefAcademicProfiles extends ListRecords
{
    protected static string $resource = DistrictChiefAcademicProfileResource::class;

    protected static ?string $title = 'Informations académiques des médecins chefs de district';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
