<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Campaign;
use App\Models\InvitationToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvitationToken>
 */
class InvitationTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'campaign_id' => Campaign::factory(),
            'token' => (string) Str::uuid(),
            'expires_at' => now()->addDays(config('recrutement.invitation_token_validity_days')),
        ];
    }
}
