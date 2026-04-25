<?php

namespace App\Models;

use App\Enums\InvitationChannel;
use Database\Factories\InvitationTokenFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InvitationToken extends Model
{
    /** @use HasFactory<InvitationTokenFactory> */
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'campaign_id',
        'token',
        'expires_at',
        'used_at',
        'notification_sent_at',
        'notification_channel',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'notification_sent_at' => 'datetime',
            'revoked_at' => 'datetime',
            'notification_channel' => InvitationChannel::class,
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function submission(): HasOne
    {
        return $this->hasOne(Submission::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isActive(): bool
    {
        return ! $this->isExpired() && ! $this->isRevoked();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')->where('expires_at', '>', now());
    }
}
