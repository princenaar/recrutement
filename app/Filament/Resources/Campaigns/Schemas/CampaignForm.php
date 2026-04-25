<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Enums\CampaignStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Intitulé')
                    ->required()
                    ->maxLength(255),
                Select::make('status')
                    ->label('Statut')
                    ->options(CampaignStatus::class)
                    ->default(CampaignStatus::Draft->value)
                    ->required(),
                DatePicker::make('starts_at')
                    ->label('Début'),
                DatePicker::make('ends_at')
                    ->label('Fin'),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
