<?php

namespace App\Models;

use App\Support\CnisPositions;
use Database\Factories\CnisPositionInterestFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnisPositionInterest extends Model
{
    /** @use HasFactory<CnisPositionInterestFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'not_interested',
        'first_choice',
        'second_choice',
        'third_choice',
    ];

    protected function casts(): array
    {
        return [
            'not_interested' => 'boolean',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    protected function interestStatusLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->not_interested ? 'Pas intéressé' : 'Intéressé');
    }

    protected function firstChoiceLabel(): Attribute
    {
        return Attribute::get(fn (): ?string => CnisPositions::title($this->first_choice));
    }

    protected function secondChoiceLabel(): Attribute
    {
        return Attribute::get(fn (): ?string => CnisPositions::title($this->second_choice));
    }

    protected function thirdChoiceLabel(): Attribute
    {
        return Attribute::get(fn (): ?string => CnisPositions::title($this->third_choice));
    }
}
