<?php

namespace App\Http\Controllers;

use App\Models\CommunicationApplication;
use App\Support\CommunicationCall;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class CommunicationApplicationController extends Controller
{
    public function show(Request $request): View
    {
        return view('communication-applications.form', [
            'positions' => CommunicationCall::positions(),
            'administrativeStatuses' => CommunicationCall::administrativeStatuses(),
            'isOpen' => CommunicationCall::isOpen(),
            'maxUploadLabel' => CommunicationCall::maxUploadLabel(),
            'maxUploadBytes' => CommunicationCall::maxUploadKilobytes() * 1024,
            'uploadError' => $request->query('upload_error') === 'too_large',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! CommunicationCall::isOpen()) {
            return redirect()->route('communication-applications.form')
                ->withErrors(['closed' => 'Cet appel est clos depuis le 14 novembre 2026 à 00 h 00 (heure de Dakar).']);
        }

        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['bail', 'required', 'string', 'email', 'max:255', Rule::unique(CommunicationApplication::class)->where('position', is_string($request->input('position')) ? $request->input('position') : '')],
            'phone' => ['required', 'string', 'max:50'],
            'administrative_status' => ['required', 'string', Rule::in(array_keys(CommunicationCall::administrativeStatuses()))],
            'current_structure' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', Rule::in(array_keys(CommunicationCall::positionOptions()))],
            'motivation' => ['required', 'string', 'max:10000'],
            'cv' => ['required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:'.CommunicationCall::maxUploadKilobytes()],
        ], [
            'required' => 'Le champ :attribute est obligatoire.',
            'string' => 'Le champ :attribute doit être un texte.',
            'max.string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
            'email.unique' => $this->duplicateMessage(),
            'administrative_status.in' => 'Veuillez sélectionner Fonctionnaire ou Contractuel.',
            'position.in' => 'Veuillez sélectionner un poste proposé dans cet appel.',
            'cv.file' => 'Veuillez joindre un fichier PDF valide.',
            'cv.mimes' => 'Le CV doit être au format PDF.',
            'cv.extensions' => 'Le CV doit porter l’extension .pdf.',
            'cv.max' => 'Le CV ne doit pas dépasser '.CommunicationCall::maxUploadLabel().'.',
            'cv.uploaded' => 'Le CV n’a pas pu être reçu. Vérifiez sa taille ('.CommunicationCall::maxUploadLabel().' maximum), puis réessayez.',
        ], [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'email' => 'e-mail',
            'phone' => 'téléphone',
            'administrative_status' => 'statut administratif',
            'current_structure' => 'structure d’affectation',
            'position' => 'poste',
            'motivation' => 'motivation',
            'cv' => 'CV',
        ]);

        $path = 'communication-applications/'.Str::uuid().'/cv.pdf';

        try {
            DB::transaction(function () use ($request, $validated, $path): void {
                CommunicationApplication::create([
                    ...collect($validated)->except('cv')->all(),
                    'first_name' => str($validated['first_name'])->squish()->toString(),
                    'last_name' => str($validated['last_name'])->squish()->toString(),
                    'current_structure' => str($validated['current_structure'])->squish()->toString(),
                    'cv_path' => $path,
                ]);

                $storedPath = $request->file('cv')->storeAs(dirname($path), 'cv.pdf', ['disk' => 'private']);

                if ($storedPath === false) {
                    throw new RuntimeException('Le CV n’a pas pu être enregistré.');
                }
            });
        } catch (UniqueConstraintViolationException $exception) {
            Storage::disk('private')->delete($path);

            throw ValidationException::withMessages(['email' => $this->duplicateMessage()]);
        } catch (Throwable $exception) {
            Storage::disk('private')->delete($path);
            report($exception);

            return back()->withInput($request->except('cv'))
                ->withErrors(['cv' => 'Votre candidature n’a pas pu être enregistrée. Veuillez réessayer en joignant à nouveau votre CV.']);
        }

        return redirect()->route('communication-applications.form')
            ->with('status', 'Votre candidature a bien été enregistrée.')
            ->with('communication_application_submitted', true);
    }

    private function duplicateMessage(): string
    {
        return 'Une candidature a déjà été déposée avec cette adresse e-mail pour ce poste. Le dossier existant est conservé.';
    }
}
