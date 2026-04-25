<?php

namespace Database\Factories;

use App\Models\Diploma;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Diploma>
 */
class DiplomaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'title' => fake()->randomElement(['Licence Informatique', 'Master MIAGE', 'Doctorat Médecine', 'BTS Gestion']),
            'institution' => fake()->randomElement(['UCAD', 'UGB', 'UADB', 'UAM']),
            'year' => fake()->numberBetween(1990, 2024),
            'file_path' => 'submissions/fake-token/diplomas/'.fake()->uuid().'.pdf',
        ];
    }
}
