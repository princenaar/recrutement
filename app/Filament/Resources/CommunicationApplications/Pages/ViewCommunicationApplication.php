<?php

namespace App\Filament\Resources\CommunicationApplications\Pages;

use App\Filament\Resources\CommunicationApplications\CommunicationApplicationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunicationApplication extends ViewRecord
{
    protected static string $resource = CommunicationApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
