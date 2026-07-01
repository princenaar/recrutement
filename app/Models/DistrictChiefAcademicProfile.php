<?php

namespace App\Models;

use Database\Factories\DistrictChiefAcademicProfileFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistrictChiefAcademicProfile extends Model
{
    /** @use HasFactory<DistrictChiefAcademicProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'service_start_date',
        'training_certificate_path',
    ];

    protected function casts(): array
    {
        return [
            'service_start_date' => 'date',
        ];
    }

    public function diplomas(): HasMany
    {
        return $this->hasMany(DistrictChiefDiploma::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    protected function hasTrainingCertificate(): Attribute
    {
        return Attribute::get(fn (): bool => filled($this->training_certificate_path));
    }
}
