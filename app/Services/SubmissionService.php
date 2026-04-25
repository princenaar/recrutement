<?php

namespace App\Services;

use App\Enums\PositionStatus;
use App\Enums\SubmissionStatus;
use App\Exceptions\InvalidSubmissionFileException;
use App\Models\Diploma;
use App\Models\InvitationToken;
use App\Models\Position;
use App\Models\Submission;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class SubmissionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function saveDraft(InvitationToken $token, array $data, ?UploadedFile $cv): Submission
    {
        if ($cv !== null) {
            $this->assertPdf($cv);
        }

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

        $submission = $existing ?? Submission::firstOrNew([
            'invitation_token_id' => $token->id,
            'agent_id' => $token->agent_id,
            'position_id' => $positionId,
        ]);

        if (! $submission->exists) {
            $submission->position_id = $positionId;
        }

        foreach (['current_structure', 'current_service', 'service_entry_date', 'motivation_note'] as $field) {
            if (array_key_exists($field, $data)) {
                $submission->{$field} = $data[$field];
            }
        }

        if ($cv !== null) {
            $this->replaceCv($submission, $token, $cv);

            if ($submission->submitted_at === null) {
                $submission->submitted_at = now();
                $submission->status = SubmissionStatus::Submitted;
            }
        } elseif (! $submission->exists) {
            $submission->status = SubmissionStatus::Draft;
        }

        $submission->last_updated_at = now();
        $submission->save();

        return $submission;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addDiploma(Submission $submission, array $data, UploadedFile $file): Diploma
    {
        $this->assertPdf($file);

        $token = $submission->invitationToken()->firstOrFail();
        $path = $file->storeAs(
            "submissions/{$token->token}/diplomas",
            Str::uuid().'.pdf',
            ['disk' => $this->diskName()]
        );

        return $submission->diplomas()->create([
            'title' => $data['title'] ?? null,
            'institution' => $data['institution'] ?? null,
            'year' => $data['year'] ?? null,
            'file_path' => $path,
        ]);
    }

    public function removeDiploma(Diploma $diploma): void
    {
        $this->disk()->delete($diploma->file_path);
        $diploma->delete();
    }

    private function replaceCv(Submission $submission, InvitationToken $token, UploadedFile $cv): void
    {
        if ($submission->cv_path !== null) {
            $this->disk()->delete($submission->cv_path);
        }

        $path = $cv->storeAs(
            "submissions/{$token->token}",
            'cv.pdf',
            ['disk' => $this->diskName()]
        );

        $submission->cv_path = $path;
    }

    private function assertPdf(UploadedFile $file): void
    {
        $maxKb = (int) config('recrutement.upload_max_size_kb');
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        if ($extension !== 'pdf' || $mime !== 'application/pdf') {
            throw InvalidSubmissionFileException::notPdf($file->getClientOriginalName());
        }

        if ($file->getSize() > $maxKb * 1024) {
            throw InvalidSubmissionFileException::tooLarge($file->getClientOriginalName(), $maxKb);
        }
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

    private function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    private function diskName(): string
    {
        return (string) config('recrutement.storage_disk');
    }
}
