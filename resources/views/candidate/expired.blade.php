@extends('layouts.candidate')

@section('title', 'Lien expiré')

@section('content')
    <section class="rounded-lg border border-amber-200 bg-amber-50 p-6">
        <h2 class="text-lg font-semibold text-amber-900">Ce lien d'invitation a expiré</h2>
        <p class="mt-2 text-sm text-amber-900">
            Le délai de 7 jours pour candidater est dépassé. Si vous souhaitez quand même soumettre votre dossier,
            veuillez contacter la DRH du Ministère de la Santé et de l'Hygiène Publique pour obtenir un nouveau lien.
        </p>
    </section>
@endsection
