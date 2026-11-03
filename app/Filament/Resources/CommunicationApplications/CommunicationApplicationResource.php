<?php

namespace App\Filament\Resources\CommunicationApplications;

use App\Filament\Resources\CommunicationApplications\Pages\ListCommunicationApplications;
use App\Filament\Resources\CommunicationApplications\Pages\ViewCommunicationApplication;
use App\Filament\Resources\CommunicationApplications\Schemas\CommunicationApplicationInfolist;
use App\Filament\Resources\CommunicationApplications\Tables\CommunicationApplicationsTable;
use App\Models\CommunicationApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CommunicationApplicationResource extends Resource
{
    protected static ?string $model = CommunicationApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Candidatures communication';

    protected static string|\UnitEnum|null $navigationGroup = 'Cellule de communication';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getModelLabel(): string
    {
        return 'candidature communication';
    }

    public static function getPluralModelLabel(): string
    {
        return 'candidatures communication';
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommunicationApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommunicationApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommunicationApplications::route('/'),
            'view' => ViewCommunicationApplication::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
