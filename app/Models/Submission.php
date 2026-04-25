<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;

    protected $fillable = [
        'invitation_token_id',
        'agent_id',
        'position_id',
        'current_structure',
        'current_service',
        'service_entry_date',
        'motivation_note',
        'cv_path',
        'submitted_at',
        'last_updated_at',
        'status',
        'shortlisted_at',
        'shortlisted_by',
        'rejection_note',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'last_updated_at' => 'datetime',
            'shortlisted_at' => 'datetime',
            'service_entry_date' => 'date',
            'status' => SubmissionStatus::class,
        ];
    }

    public function invitationToken(): BelongsTo
    {
        return $this->belongsTo(InvitationToken::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function shortlistedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shortlisted_by');
    }

    public function diplomas(): HasMany
    {
        return $this->hasMany(Diploma::class);
    }

    protected function seniorityYears(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->service_entry_date
            ? (int) now()->diffInYears($this->service_entry_date, true)
            : null);
    }
}
