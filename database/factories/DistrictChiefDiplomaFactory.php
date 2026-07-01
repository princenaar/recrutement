<?php

namespace Database\Factories;

use App\Models\DistrictChiefAcademicProfile;
use App\Models\DistrictChiefDiploma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DistrictChiefDiploma>
 */
class DistrictChiefDiplomaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'district_chief_academic_profile_id' => DistrictChiefAcademicProfile::factory(),
            'name' => fake()->randomElement(['Doctorat en médecine', 'Master en santé publique', 'DU Management sanitaire']),
            'obtained_year' => fake()->numberBetween(1990, (int) date('Y')),
            'scan_path' => 'district-chief-academic-profiles/'.fake()->randomNumber().'/diplomas/'.fake()->uuid().'.pdf',
        ];
    }
}
