<?php

namespace App\Models;

use Database\Factories\DistrictChiefDiplomaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistrictChiefDiploma extends Model
{
    /** @use HasFactory<DistrictChiefDiplomaFactory> */
    use HasFactory;

    protected $fillable = [
        'district_chief_academic_profile_id',
        'name',
        'obtained_year',
        'scan_path',
    ];

    protected function casts(): array
    {
        return [
            'obtained_year' => 'integer',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(DistrictChiefAcademicProfile::class, 'district_chief_academic_profile_id');
    }
}
