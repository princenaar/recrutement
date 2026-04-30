<?php

namespace App\Models;

use App\Enums\CampaignFormType;
use App\Enums\CampaignStatus;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'form_type',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'form_type' => CampaignFormType::class,
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function invitationTokens(): HasMany
    {
        return $this->hasMany(InvitationToken::class);
    }
}
