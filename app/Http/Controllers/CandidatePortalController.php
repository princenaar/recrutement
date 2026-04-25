<?php

namespace App\Http\Controllers;

use App\Enums\PositionStatus;
use App\Exceptions\InvalidSubmissionFileException;
use App\Models\Diploma;
use App\Models\InvitationToken;
use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Illuminate\View\View as ViewContract;
use Symfony\Component\HttpFoundation\Response;

class CandidatePortalController extends Controller
{
    public function __construct(private readonly SubmissionService $submissions) {}

    public function show(string $token): Response|ViewContract
    {
        $invitation = $this->resolveToken($token);

        if (! $invitation instanceof InvitationToken) {
            return $invitation;
        }

        $invitation->loadMissing(['agent', 'campaign.positions', 'submission.diplomas', 'submission.position']);

        $positions = $invitation->campaign->positions
            ->where('status', PositionStatus::Open)
            ->values();

        return view('candidate.portal', [
            'token' => $invitation,
            'agent' => $invitation->agent,
            'campaign' => $invitation->campaign,
            'positions' => $positions,
            'submission' => $invitation->submission,
        ]);
    }

    public function save(Request $request, string $token): Response|RedirectResponse|ViewContract
    {
        $invitation = $this->resolveToken($token);

        if (! $invitation instanceof InvitationToken) {
            return $invitation;
        }

        $maxKb = (int) config('recrutement.upload_max_size_kb');

        $existing = Submission::query()
            ->where('invitation_token_id', $invitation->id)
            ->first();

        $positionRule = $existing?->submitted_at !== null
            ? ['nullable']
            : [
                'required',
                Rule::exists('positions', 'id')->where(
                    fn ($q) => $q->where('campaign_id', $invitation->campaign_id)
                        ->where('status', PositionStatus::Open->value)
                ),
            ];

        $validated = $request->validate([
            'position_id' => $positionRule,
            'current_structure' => ['nullable', 'string', 'max:255'],
            'current_service' => ['nullable', 'string', 'max:255'],
            'service_entry_date' => ['nullable', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:today'],
            'motivation_note' => ['nullable', 'string', 'max:5000'],
            'cv' => ['nullable', 'file', 'mimes:pdf', "max:{$maxKb}"],
        ]);

        // Once the candidate has submitted, the position is locked.
        if ($existing?->submitted_at !== null) {
            $validated['position_id'] = $existing->position_id;
        }

        try {
            $this->submissions->saveDraft(
                $invitation,
                array_intersect_key($validated, array_flip([
                    'position_id',
                    'current_structure',
                    'current_service',
                    'service_entry_date',
                    'motivation_note',
                ])),
                $request->file('cv')
            );
        } catch (InvalidSubmissionFileException $e) {
            return back()->withErrors(['cv' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('candidate.portal', ['token' => $invitation->token])
            ->with('status', 'Votre dossier a été enregistré.');
    }

    public function addDiploma(Request $request, string $token): Response|RedirectResponse|ViewContract
    {
        $invitation = $this->resolveToken($token);

        if (! $invitation instanceof InvitationToken) {
            return $invitation;
        }

        $submission = Submission::query()
            ->where('invitation_token_id', $invitation->id)
            ->first();

        if ($submission === null) {
            return back()->withErrors([
                'file' => 'Veuillez sélectionner un poste et enregistrer votre dossier avant d\'ajouter un diplôme.',
            ])->withInput();
        }

        $maxKb = (int) config('recrutement.upload_max_size_kb');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:'.(int) date('Y')],
            'file' => ['required', 'file', 'mimes:pdf', "max:{$maxKb}"],
        ]);

        try {
            $this->submissions->addDiploma(
                $submission,
                array_intersect_key($validated, array_flip(['title', 'institution', 'year'])),
                $request->file('file')
            );
        } catch (InvalidSubmissionFileException $e) {
            return back()->withErrors(['file' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('candidate.portal', ['token' => $invitation->token])
            ->with('status', 'Diplôme ajouté.');
    }

    public function removeDiploma(string $token, Diploma $diploma): Response|RedirectResponse|ViewContract
    {
        $invitation = $this->resolveToken($token);

        if (! $invitation instanceof InvitationToken) {
            return $invitation;
        }

        $diploma->loadMissing('submission');

        if ($diploma->submission?->invitation_token_id !== $invitation->id) {
            abort(404);
        }

        $this->submissions->removeDiploma($diploma);

        return redirect()
            ->route('candidate.portal', ['token' => $invitation->token])
            ->with('status', 'Diplôme supprimé.');
    }

    private function resolveToken(string $token): InvitationToken|ViewContract
    {
        $invitation = InvitationToken::where('token', $token)->first();

        if ($invitation === null) {
            abort(404);
        }

        if ($invitation->isRevoked()) {
            return View::make('candidate.error', [
                'reason' => 'revoked',
                'token' => $invitation,
            ]);
        }

        if ($invitation->isExpired()) {
            return View::make('candidate.expired', ['token' => $invitation]);
        }

        return $invitation;
    }
}
