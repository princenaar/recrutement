<?php

namespace Database\Factories;

use App\Enums\CampaignFormType;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => CampaignStatus::Active,
            'form_type' => CampaignFormType::DocumentDossier,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ];
    }
}
