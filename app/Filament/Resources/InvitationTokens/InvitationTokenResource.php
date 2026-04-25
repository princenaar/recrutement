<?php

namespace App\Filament\Resources\InvitationTokens;

use App\Filament\Resources\InvitationTokens\Pages\ListInvitationTokens;
use App\Filament\Resources\InvitationTokens\Tables\InvitationTokensTable;
use App\Models\InvitationToken;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InvitationTokenResource extends Resource
{
    protected static ?string $model = InvitationToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $navigationLabel = 'Invitations';

    protected static string|\UnitEnum|null $navigationGroup = 'Recrutement';

    protected static ?int $navigationSort = 40;

    public static function table(Table $table): Table
    {
        return InvitationTokensTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvitationTokens::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
