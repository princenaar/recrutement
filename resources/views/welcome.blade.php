@extends('layouts.candidate')

@section('title', 'Accueil')

@section('content')
    <section class="rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
        <div class="text-center max-w-2xl mx-auto">
            <p class="text-xs uppercase tracking-widest text-emerald-700 font-semibold">
                Direction des Ressources Humaines
            </p>
            <h1 class="mt-3 text-3xl font-bold text-gray-900">
                Recrutement interne
            </h1>
            <p class="mt-4 text-sm text-gray-600 leading-relaxed">
                Cette plateforme permet aux agents du Ministère de la Santé et de l'Hygiène Publique
                de soumettre leur dossier de candidature aux campagnes de recrutement interne.
                <br>
                <strong>L'accès se fait uniquement via un lien personnel</strong>
                envoyé par e-mail, SMS ou WhatsApp.
            </p>
        </div>
    </section>

    <section class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <article class="rounded-lg border border-gray-200 bg-white p-6 text-center">
            <div class="mx-auto h-10 w-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold">1</div>
            <h2 class="mt-3 text-sm font-semibold text-gray-900">Recevez votre lien</h2>
            <p class="mt-2 text-xs text-gray-600 leading-relaxed">
                La DRH vous envoie un lien personnel valable 7 jours pour candidater à la campagne en cours.
            </p>
        </article>

        <article class="rounded-lg border border-gray-200 bg-white p-6 text-center">
            <div class="mx-auto h-10 w-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold">2</div>
            <h2 class="mt-3 text-sm font-semibold text-gray-900">Choisissez votre poste</h2>
            <p class="mt-2 text-xs text-gray-600 leading-relaxed">
                Sélectionnez le poste qui vous intéresse parmi ceux ouverts dans la campagne, puis remplissez votre dossier (CV, diplômes).
            </p>
        </article>

        <article class="rounded-lg border border-gray-200 bg-white p-6 text-center">
            <div class="mx-auto h-10 w-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold">3</div>
            <h2 class="mt-3 text-sm font-semibold text-gray-900">Suivez votre candidature</h2>
            <p class="mt-2 text-xs text-gray-600 leading-relaxed">
                Vous pouvez revenir compléter ou modifier votre dossier tant que le lien est actif.
            </p>
        </article>
    </section>

    <section class="mt-8 rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
        <p>
            <strong>Vous n'avez pas reçu de lien ?</strong> Contactez la Direction des Ressources Humaines
            du Ministère de la Santé et de l'Hygiène Publique pour vérifier votre éligibilité aux campagnes en cours.
        </p>
    </section>

    <p class="mt-8 text-center text-xs text-gray-500">
        Espace administrateur :
        <a href="{{ url('/admin') }}" class="underline hover:text-emerald-700">accéder au back-office</a>
    </p>
@endsection
