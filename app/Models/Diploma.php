<?php

namespace App\Models;

use Database\Factories\DiplomaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diploma extends Model
{
    /** @use HasFactory<DiplomaFactory> */
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'title',
        'institution',
        'year',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
