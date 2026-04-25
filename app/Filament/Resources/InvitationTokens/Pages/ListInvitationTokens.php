<?php

namespace App\Filament\Resources\InvitationTokens\Pages;

use App\Filament\Resources\InvitationTokens\InvitationTokenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvitationTokens extends ListRecords
{
    protected static string $resource = InvitationTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
