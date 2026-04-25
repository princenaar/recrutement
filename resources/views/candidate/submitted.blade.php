@extends('layouts.candidate')

@section('title', 'Dossier soumis')

<section class="rounded-lg border border-green-200 bg-green-50 p-6">
    <h2 class="text-lg font-semibold text-green-900">Votre dossier a bien été enregistré</h2>
    <p class="mt-2 text-sm text-green-900">
        Vous pouvez modifier votre dossier (informations actualisables, CV, diplômes) jusqu'à
        l'expiration de votre lien personnel. La DRH examinera ensuite votre candidature.
    </p>

    @isset($token)
        <p class="mt-4 text-sm text-green-900">
            <a href="{{ route('candidate.portal', ['token' => $token->token]) }}" class="underline">
                Retour à mon dossier
            </a>
        </p>
    @endisset
</section>
