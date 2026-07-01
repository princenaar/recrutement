<?php

namespace App\Filament\Resources\DistrictChiefAcademicProfiles;

use App\Filament\Resources\DistrictChiefAcademicProfiles\Pages\ListDistrictChiefAcademicProfiles;
use App\Filament\Resources\DistrictChiefAcademicProfiles\Pages\ViewDistrictChiefAcademicProfile;
use App\Filament\Resources\DistrictChiefAcademicProfiles\Schemas\DistrictChiefAcademicProfileForm;
use App\Filament\Resources\DistrictChiefAcademicProfiles\Schemas\DistrictChiefAcademicProfileInfolist;
use App\Filament\Resources\DistrictChiefAcademicProfiles\Tables\DistrictChiefAcademicProfilesTable;
use App\Models\DistrictChiefAcademicProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DistrictChiefAcademicProfileResource extends Resource
{
    protected static ?string $model = DistrictChiefAcademicProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Informations académiques';

    protected static string|\UnitEnum|null $navigationGroup = 'Médecins chefs de district';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getModelLabel(): string
    {
        return 'information académique médecin chef';
    }

    public static function getPluralModelLabel(): string
    {
        return 'informations académiques médecins chefs';
    }

    public static function form(Schema $schema): Schema
    {
        return DistrictChiefAcademicProfileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DistrictChiefAcademicProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DistrictChiefAcademicProfilesTable::configure($table);
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
            'index' => ListDistrictChiefAcademicProfiles::route('/'),
            'view' => ViewDistrictChiefAcademicProfile::route('/{record}'),
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
