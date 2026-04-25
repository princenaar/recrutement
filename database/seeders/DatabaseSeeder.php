<?php

namespace Database\Seeders;

use App\Enums\CampaignStatus;
use App\Enums\PositionStatus;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\InvitationToken;
use App\Models\Position;
use App\Models\User;
use App\Services\AgentImportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->importAgents();
        $campaign = $this->seedCampaignAndPositions();
        $this->seedInvitationTokens($campaign);
    }

    private function seedAdmin(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@recrutement.test'],
            [
                'name' => 'Admin DRH',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }

    private function importAgents(): void
    {
        $fixture = database_path('seeders/data/agents_ihris_sample.xlsx');

        if (! is_file($fixture)) {
            $this->command?->warn("Fixture iHRIS introuvable : {$fixture}. Import sauté.");

            return;
        }

        $result = app(AgentImportService::class)->import($fixture);
        $this->command?->info(sprintf(
            'Agents importés : %d créés · %d mis à jour · %d ignorés',
            $result->created,
            $result->updated,
            $result->skipped,
        ));
    }

    private function seedCampaignAndPositions(): Campaign
    {
        $campaign = Campaign::updateOrCreate(
            ['title' => 'Recrutement DSI 2026'],
            [
                'description' => 'Campagne de recrutement de cadres pour la Direction des Systèmes d\'Information.',
                'status' => CampaignStatus::Active,
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->endOfMonth()->addMonth(),
            ]
        );

        Position::updateOrCreate(
            ['campaign_id' => $campaign->id, 'title' => 'Ingénieur DevOps'],
            [
                'description' => 'Mise en place et exploitation des plateformes cloud du Ministère.',
                'required_profile' => 'Bac+5 informatique, 3 ans d\'expérience, maîtrise Kubernetes.',
                'status' => PositionStatus::Open,
            ]
        );

        Position::updateOrCreate(
            ['campaign_id' => $campaign->id, 'title' => 'Chef de projet SI Santé'],
            [
                'description' => 'Pilotage des projets de digitalisation des structures sanitaires.',
                'required_profile' => 'Bac+5, 5 ans d\'expérience, certification PMP appréciée.',
                'status' => PositionStatus::Open,
            ]
        );

        return $campaign;
    }

    private function seedInvitationTokens(Campaign $campaign): void
    {
        $agents = Agent::query()->orderBy('id')->take(5)->get();

        if ($agents->count() < 5) {
            $this->command?->warn('Moins de 5 agents disponibles — seeding des tokens partiel.');
        }

        $plans = [
            ['expires_at' => now()->addDays(7), 'revoked_at' => null],
            ['expires_at' => now()->addDays(7), 'revoked_at' => null],
            ['expires_at' => now()->subDay(), 'revoked_at' => null],
            ['expires_at' => now()->subDays(3), 'revoked_at' => null],
            ['expires_at' => now()->addDays(7), 'revoked_at' => now()],
        ];

        $created = collect();

        foreach ($agents as $i => $agent) {
            if (! isset($plans[$i])) {
                break;
            }

            $plan = $plans[$i];

            $token = InvitationToken::updateOrCreate(
                ['agent_id' => $agent->id, 'campaign_id' => $campaign->id],
                [
                    'token' => (string) Str::uuid(),
                    'expires_at' => $plan['expires_at'],
                    'revoked_at' => $plan['revoked_at'],
                ]
            );

            $created->push($token);
        }

        $this->command?->info('Tokens d\'invitation seedés (2 actifs · 2 expirés · 1 révoqué).');
        $this->command?->table(
            ['Agent', 'Campagne', 'État', 'URL'],
            $created->map(fn (InvitationToken $t) => [
                $t->agent->matricule,
                $campaign->title,
                match (true) {
                    $t->isRevoked() => 'révoqué',
                    $t->isExpired() => 'expiré',
                    default => 'actif',
                },
                route('candidate.portal', ['token' => $t->token]),
            ])->all()
        );
    }
}
