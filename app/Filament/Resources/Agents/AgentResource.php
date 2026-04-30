<?php

namespace App\Filament\Resources\Agents;

use App\Filament\Resources\Agents\Pages\ListAgents;
use App\Filament\Resources\Agents\Tables\AgentsTable;
use App\Models\Agent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Candidats';

    protected static string|\UnitEnum|null $navigationGroup = 'Recrutement';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'matricule';

    public static function getModelLabel(): string
    {
        return 'candidat';
    }

    public static function getPluralModelLabel(): string
    {
        return 'candidats';
    }

    public static function table(Table $table): Table
    {
        return AgentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgents::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
