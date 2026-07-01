<?php

namespace Database\Factories;

use App\Models\DistrictChiefAcademicProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DistrictChiefAcademicProfile>
 */
class DistrictChiefAcademicProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'service_start_date' => fake()->dateTimeBetween('-10 years', '-1 month')->format('Y-m-d'),
            'training_certificate_path' => null,
        ];
    }

    public function withTrainingCertificate(): self
    {
        return $this->state(fn (): array => [
            'training_certificate_path' => 'district-chief-academic-profiles/'.fake()->randomNumber().'/certificate/'.fake()->uuid().'.pdf',
        ]);
    }
}
