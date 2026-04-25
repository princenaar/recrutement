<?php

namespace App\Models;

use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    /** @use HasFactory<AgentFactory> */
    use HasFactory;

    protected $fillable = [
        'matricule',
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'nationality',
        'email',
        'phone',
        'category',
        'current_position',
        'position_start_date',
        'service',
        'structure',
        'district',
        'region',
        'employer',
        'contract_type',
        'agent_status',
        'entry_date',
        'marital_status',
        'ihris_imported_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'position_start_date' => 'date',
            'entry_date' => 'date',
            'ihris_imported_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function invitationTokens(): HasMany
    {
        return $this->hasMany(InvitationToken::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
