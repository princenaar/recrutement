<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'matricule' => fake()->unique()->numerify('MAT######'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => fake()->randomElement(['M', 'F']),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-25 years')->format('Y-m-d'),
            'nationality' => 'Sénégalaise',
            'email' => fake()->optional(0.7)->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'category' => fake()->randomElement(['A', 'B', 'C']),
            'current_position' => fake()->jobTitle(),
            'position_start_date' => fake()->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d'),
            'service' => fake()->words(2, true),
            'structure' => fake()->randomElement(['Hôpital Principal', 'Centre Hospitalier Régional', 'District Sanitaire', 'DRH Centrale']),
            'district' => fake()->city(),
            'region' => fake()->randomElement(['Dakar', 'Thiès', 'Saint-Louis', 'Diourbel', 'Kaolack', 'Ziguinchor']),
            'employer' => 'MSHP',
            'contract_type' => fake()->randomElement(['CDI', 'CDD']),
            'agent_status' => fake()->randomElement(['Fonctionnaire', 'Contractuel', 'Non Fonctionnaire']),
            'entry_date' => fake()->dateTimeBetween('-20 years', '-1 year')->format('Y-m-d'),
            'marital_status' => fake()->randomElement(['Célibataire', 'Marié(e)', 'Divorcé(e)', 'Veuf/Veuve']),
            'ihris_imported_at' => now(),
        ];
    }
}
