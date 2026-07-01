<?php

namespace App\Filament\Resources\DistrictChiefAcademicProfiles\Pages;

use App\Filament\Resources\DistrictChiefAcademicProfiles\DistrictChiefAcademicProfileResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDistrictChiefAcademicProfile extends ViewRecord
{
    protected static string $resource = DistrictChiefAcademicProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
