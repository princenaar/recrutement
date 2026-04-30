<?php

namespace App\Services;

use App\Enums\PositionStatus;
use App\Enums\SubmissionStatus;
use App\Models\InvitationToken;
use App\Models\Position;
use App\Models\Submission;
use RuntimeException;

class CriteriaSubmissionService
{
    public const MAX_RAW_SCORE = 65;

    public const REGIONS = [
        'Dakar',
        'Thiès',
        'Diourbel',
        'Fatick',
        'Kaolack',
        'Saint-Louis',
        'Louga',
        'Kaffrine',
        'Ziguinchor',
        'Kolda',
        'Sédhiou',
        'Matam',
        'Tambacounda',
        'Kédougou',
    ];

    public const REGION_POINTS = [
        'Dakar' => 0,
        'Thiès' => 1,
        'Diourbel' => 2,
        'Fatick' => 2,
        'Kaolack' => 2,
        'Saint-Louis' => 2,
        'Louga' => 3,
        'Kaffrine' => 3,
        'Ziguinchor' => 3,
        'Kolda' => 4,
        'Sédhiou' => 4,
        'Matam' => 4,
        'Tambacounda' => 5,
        'Kédougou' => 5,
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(InvitationToken $token, array $data): Submission
    {
        $existing = Submission::query()
            ->where('invitation_token_id', $token->id)
            ->first();

        $positionId = $data['position_id'] ?? $existing?->position_id;

        if ($positionId === null) {
            throw new RuntimeException('Aucun poste sélectionné pour cette candidature.');
        }

        if ($existing === null) {
            $this->assertPositionIsOpenForCampaign((int) $positionId, $token);
        }

        $responses = $this->responsesFromData($data);
        $regions = array_values($data['region_choices'] ?? []);
        $scoreBreakdown = $this->scoreBreakdown($responses, $regions);
        $rawScore = array_sum($scoreBreakdown);

        $submission = $existing ?? Submission::firstOrNew([
            'invitation_token_id' => $token->id,
            'agent_id' => $token->agent_id,
            'position_id' => $positionId,
        ]);

        if (! $submission->exists) {
            $submission->position_id = $positionId;
        }

        $submission->current_structure = $responses['currently_active'] === 'yes'
            ? $responses['activity_location']
            : ($token->agent?->structure ?? 'Non renseigné');
        $submission->current_service = 'Non renseigné';
        $submission->motivation_note = $responses['motivation_note'];
        $submission->responses = $responses;
        $submission->region_choices = $regions;
        $submission->score_breakdown = $scoreBreakdown;
        $submission->raw_score = $rawScore;
        $submission->normalized_score = round($rawScore / self::MAX_RAW_SCORE * 100, 2);

        if ($submission->submitted_at === null) {
            $submission->submitted_at = now();
            $submission->status = SubmissionStatus::Submitted;
        }

        $submission->last_updated_at = now();
        $submission->save();

        return $submission;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function responsesFromData(array $data): array
    {
        return [
            'currently_active' => $data['currently_active'],
            'activity_location' => $data['currently_active'] === 'yes'
                ? trim((string) $data['activity_location'])
                : null,
            'degree_level' => $data['degree_level'],
            'experience_years' => (int) $data['experience_years'],
            'knows_snis' => $data['knows_snis'],
            'dhis2_level' => $data['dhis2_level'],
            'computer_skills' => $data['computer_skills'],
            'motivation_note' => $data['motivation_note'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $responses
     * @param  array<int, string>  $regions
     * @return array<string, int>
     */
    private function scoreBreakdown(array $responses, array $regions): array
    {
        return [
            'degree' => match ($responses['degree_level']) {
                'master_data_health' => 20,
                'licence_data_health' => 15,
                'other_relevant' => 10,
                default => 0,
            },
            'experience' => min(10, max(0, (int) $responses['experience_years']) * 2),
            'snis' => $responses['knows_snis'] === 'yes' ? 10 : 0,
            'dhis2' => match ($responses['dhis2_level']) {
                'advanced' => 15,
                'basic' => 8,
                default => 0,
            },
            'computer_skills' => $responses['computer_skills'] === 'yes' ? 5 : 0,
            'terrain_motivation' => collect($regions)
                ->map(fn (string $region): int => self::REGION_POINTS[$region] ?? 0)
                ->max() ?? 0,
        ];
    }

    private function assertPositionIsOpenForCampaign(int $positionId, InvitationToken $token): void
    {
        $exists = Position::query()
            ->whereKey($positionId)
            ->where('campaign_id', $token->campaign_id)
            ->where('status', PositionStatus::Open)
            ->exists();

        if (! $exists) {
            throw new RuntimeException('Le poste sélectionné ne fait pas partie des postes ouverts de cette campagne.');
        }
    }
}
