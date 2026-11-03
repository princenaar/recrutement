<?php

namespace App\Filament\Resources\CommunicationApplications\Pages;

use App\Filament\Resources\CommunicationApplications\CommunicationApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListCommunicationApplications extends ListRecords
{
    protected static string $resource = CommunicationApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
