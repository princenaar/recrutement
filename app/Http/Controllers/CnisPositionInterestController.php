<?php

namespace App\Http\Controllers;

use App\Models\CnisPositionInterest;
use App\Support\CnisPositions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;
use Illuminate\View\View;

class CnisPositionInterestController extends Controller
{
    public function show(): View
    {
        return view('cnis.positions', [
            'positions' => CnisPositions::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $positionKeys = CnisPositions::keys();

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'interest_status' => ['required', Rule::in(['interested', 'not_interested'])],
            'first_choice' => ['nullable', 'string', Rule::in($positionKeys)],
            'second_choice' => ['nullable', 'string', Rule::in($positionKeys)],
            'third_choice' => ['nullable', 'string', Rule::in($positionKeys)],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($request): void {
            $interestStatus = $request->input('interest_status');
            $choices = collect([
                'first_choice' => $request->input('first_choice'),
                'second_choice' => $request->input('second_choice'),
                'third_choice' => $request->input('third_choice'),
            ])->filter(fn ($choice): bool => filled($choice));

            if ($interestStatus === 'not_interested') {
                if ($choices->isNotEmpty()) {
                    $validator->errors()->add('first_choice', 'Aucun poste ne doit être sélectionné si vous indiquez ne pas être intéressé.');
                }

                return;
            }

            if ($interestStatus !== 'interested') {
                return;
            }

            if (! filled($request->input('first_choice'))) {
                $validator->errors()->add('first_choice', 'Veuillez sélectionner au moins un poste.');
            }

            if (filled($request->input('third_choice')) && ! filled($request->input('second_choice'))) {
                $validator->errors()->add('third_choice', 'Veuillez renseigner le deuxième choix avant le troisième.');
            }

            if ($choices->unique()->count() !== $choices->count()) {
                $validator->errors()->add('second_choice', 'Chaque poste ne peut être sélectionné qu’une seule fois.');
            }
        });

        $validated = $validator->validate();
        $notInterested = $validated['interest_status'] === 'not_interested';

        CnisPositionInterest::create([
            'first_name' => str($validated['first_name'])->squish()->toString(),
            'last_name' => str($validated['last_name'])->squish()->toString(),
            'not_interested' => $notInterested,
            'first_choice' => $notInterested ? null : ($validated['first_choice'] ?? null),
            'second_choice' => $notInterested ? null : ($validated['second_choice'] ?? null),
            'third_choice' => $notInterested ? null : ($validated['third_choice'] ?? null),
        ]);

        return redirect()
            ->route('cnis.positions.form')
            ->with('status', 'Votre réponse a bien été enregistrée.');
    }
}
