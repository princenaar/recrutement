<?php

namespace App\Filament\Resources\CnisPositionInterests;

use App\Filament\Resources\CnisPositionInterests\Pages\ListCnisPositionInterests;
use App\Filament\Resources\CnisPositionInterests\Pages\ViewCnisPositionInterest;
use App\Filament\Resources\CnisPositionInterests\Schemas\CnisPositionInterestForm;
use App\Filament\Resources\CnisPositionInterests\Schemas\CnisPositionInterestInfolist;
use App\Filament\Resources\CnisPositionInterests\Tables\CnisPositionInterestsTable;
use App\Models\CnisPositionInterest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CnisPositionInterestResource extends Resource
{
    protected static ?string $model = CnisPositionInterest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Choix des postes';

    protected static string|\UnitEnum|null $navigationGroup = 'CNDIS';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getModelLabel(): string
    {
        return 'choix de poste CNDIS';
    }

    public static function getPluralModelLabel(): string
    {
        return 'choix des postes CNDIS';
    }

    public static function form(Schema $schema): Schema
    {
        return CnisPositionInterestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CnisPositionInterestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CnisPositionInterestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCnisPositionInterests::route('/'),
            'view' => ViewCnisPositionInterest::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
