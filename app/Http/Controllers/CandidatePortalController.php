<?php

namespace App\Http\Controllers;

use App\Enums\PositionStatus;
use App\Exceptions\InvalidSubmissionFileException;
use App\Models\InvitationToken;
use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
            ->with('diplomas')
            ->where('invitation_token_id', $invitation->id)
            ->first();

        $positionRule = $existing?->submitted_at !== null
            ? ['required']
            : [
                'required',
                Rule::exists('positions', 'id')->where(
                    fn ($q) => $q->where('campaign_id', $invitation->campaign_id)
                        ->where('status', PositionStatus::Open->value)
                ),
            ];
        $cvRule = $existing?->cv_path ? ['nullable', 'file', 'mimes:pdf', "max:{$maxKb}"] : ['required', 'file', 'mimes:pdf', "max:{$maxKb}"];

        $validator = Validator::make($request->all(), [
            'position_id' => $positionRule,
            'current_structure' => ['required', 'string', 'max:255'],
            'current_service' => ['required', 'string', 'max:255'],
            'service_entry_date' => ['nullable', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:today'],
            'motivation_note' => ['nullable', 'string', 'max:5000'],
            'cv' => $cvRule,
            'diplomas_to_delete' => ['nullable', 'array'],
            'diplomas_to_delete.*' => ['integer'],
            'new_diplomas' => ['nullable', 'array'],
            'new_diplomas.*.title' => ['nullable', 'string', 'max:255'],
            'new_diplomas.*.institution' => ['nullable', 'string', 'max:255'],
            'new_diplomas.*.year' => ['nullable', 'integer', 'min:1950', 'max:'.(int) date('Y')],
            'new_diplomas.*.file' => ['nullable', 'file', 'mimes:pdf', "max:{$maxKb}"],
        ]);

        $validator->after(function ($validator) use ($existing, $request): void {
            $deleteIds = collect($request->input('diplomas_to_delete', []))
                ->map(fn ($id): int => (int) $id)
                ->filter()
                ->unique()
                ->values();
            $existingDiplomas = $existing?->diplomas ?? collect();

            if ($deleteIds->isNotEmpty()) {
                $ownedDeleteCount = $existingDiplomas
                    ->whereIn('id', $deleteIds->all())
                    ->count();

                if ($ownedDeleteCount !== $deleteIds->count()) {
                    $validator->errors()->add('diplomas_to_delete', 'Un diplôme sélectionné ne fait pas partie de votre dossier.');
                }
            }

            $newDiplomasCount = 0;
            $newDiplomas = $request->input('new_diplomas', []);

            foreach ($newDiplomas as $index => $diploma) {
                $file = $request->file("new_diplomas.{$index}.file");
                $hasInput = filled($diploma['title'] ?? null)
                    || filled($diploma['institution'] ?? null)
                    || filled($diploma['year'] ?? null)
                    || $file !== null;

                if (! $hasInput) {
                    continue;
                }

                if (! filled($diploma['title'] ?? null)) {
                    $validator->errors()->add("new_diplomas.{$index}.title", 'L’intitulé du diplôme est obligatoire.');
                }

                if ($file === null) {
                    $validator->errors()->add("new_diplomas.{$index}.file", 'Le fichier du diplôme est obligatoire.');
                }

                if (filled($diploma['title'] ?? null) && $file !== null) {
                    $newDiplomasCount++;
                }
            }

            $remainingDiplomasCount = max(0, $existingDiplomas->count() - $deleteIds->count()) + $newDiplomasCount;

            if ($remainingDiplomasCount < 1) {
                $validator->errors()->add('new_diplomas', 'Veuillez ajouter au moins un diplôme.');
            }
        });

        $validated = $validator->validate();

        // Once the candidate has submitted, the position is locked.
        if ($existing?->submitted_at !== null) {
            $validated['position_id'] = $existing->position_id;
        }

        try {
            DB::transaction(function () use ($existing, $invitation, $request, $validated): void {
                $submission = $this->submissions->saveDraft(
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

                $deleteIds = collect($validated['diplomas_to_delete'] ?? [])
                    ->map(fn ($id): int => (int) $id)
                    ->filter()
                    ->unique();

                if ($deleteIds->isNotEmpty()) {
                    $existing?->diplomas
                        ->whereIn('id', $deleteIds->all())
                        ->each(fn ($diploma) => $this->submissions->removeDiploma($diploma));
                }

                foreach ($validated['new_diplomas'] ?? [] as $index => $diploma) {
                    $file = $request->file("new_diplomas.{$index}.file");

                    if (! filled($diploma['title'] ?? null) || $file === null) {
                        continue;
                    }

                    $this->submissions->addDiploma(
                        $submission,
                        [
                            'title' => $diploma['title'],
                            'institution' => $diploma['institution'] ?? null,
                            'year' => $diploma['year'] ?? null,
                        ],
                        $file
                    );
                }
            });
        } catch (InvalidSubmissionFileException $e) {
            return back()->withErrors(['new_diplomas' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('candidate.portal', ['token' => $invitation->token])
            ->with('status', 'Votre dossier a été enregistré.');
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
