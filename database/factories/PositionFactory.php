<?php

namespace Database\Factories;

use App\Enums\PositionStatus;
use App\Models\Campaign;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraph(),
            'required_profile' => fake()->sentence(),
            'status' => PositionStatus::Open,
        ];
    }
}
