<?php

namespace Database\Factories;

use App\Models\CnisPositionInterest;
use App\Support\CnisPositions;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CnisPositionInterest>
 */
class CnisPositionInterestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $choices = array_values(fake()->randomElements(CnisPositions::keys(), 3));

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'not_interested' => false,
            'first_choice' => $choices[0],
            'second_choice' => $choices[1],
            'third_choice' => $choices[2],
        ];
    }

    public function notInterested(): self
    {
        return $this->state(fn (): array => [
            'not_interested' => true,
            'first_choice' => null,
            'second_choice' => null,
            'third_choice' => null,
        ]);
    }
}
