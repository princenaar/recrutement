<?php

namespace App\Models;

use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Agent extends Model
{
    /** @use HasFactory<AgentFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'matricule',
        'import_source',
        'import_name',
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
        'source_payload',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'position_start_date' => 'date',
            'entry_date' => 'date',
            'ihris_imported_at' => 'datetime',
            'source_payload' => 'array',
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
