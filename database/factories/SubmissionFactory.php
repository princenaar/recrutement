<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Agent;
use App\Models\InvitationToken;
use App\Models\Position;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invitation_token_id' => InvitationToken::factory(),
            'agent_id' => Agent::factory(),
            'position_id' => Position::factory(),
            'current_structure' => fake()->company(),
            'current_service' => fake()->words(2, true),
            'service_entry_date' => fake()->dateTimeBetween('-30 years', '-1 year')->format('Y-m-d'),
            'motivation_note' => fake()->paragraph(),
            'cv_path' => 'submissions/fake-token/cv.pdf',
            'status' => SubmissionStatus::Draft,
        ];
    }
}
