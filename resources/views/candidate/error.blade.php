@extends('layouts.candidate')

@section('title', 'Lien invalide')

@section('content')
    <section class="rounded-lg border border-red-200 bg-red-50 p-6">
        <h2 class="text-lg font-semibold text-red-900">Ce lien n'est plus valide</h2>
        <p class="mt-2 text-sm text-red-900">
            Le lien que vous utilisez a été révoqué ou n'est plus actif. Veuillez contacter l'équipe de recrutement du
            Ministère de la Santé et de l'Hygiène Publique pour obtenir des informations.
        </p>
    </section>
@endsection
