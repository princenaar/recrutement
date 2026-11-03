<?php

namespace Database\Factories;

use App\Models\CommunicationApplication;
use App\Support\CommunicationCall;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationApplication>
 */
class CommunicationApplicationFactory extends Factory
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
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+221 77 123 45 67',
            'administrative_status' => fake()->randomElement(array_keys(CommunicationCall::administrativeStatuses())),
            'current_structure' => 'Ministère de la Santé et de l’Hygiène publique',
            'position' => fake()->randomElement(array_keys(CommunicationCall::positionOptions())),
            'motivation' => fake()->paragraph(),
            'cv_path' => 'communication-applications/'.fake()->uuid().'/cv.pdf',
        ];
    }
}
