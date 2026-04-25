@extends('layouts.candidate')

@section('title', 'Mon dossier')

@php
    $maxKb = (int) config('recrutement.upload_max_size_kb');
    $maxMb = round($maxKb / 1024, 1);

    $serviceEntryDate = old(
        'service_entry_date',
        $submission?->service_entry_date?->format('Y-m-d')
            ?? $agent->entry_date?->format('Y-m-d')
    );

    $isLocked = $submission?->submitted_at !== null;
    $selectedPositionId = old('position_id', $submission?->position_id);
@endphp

@section('content')
    <section class="space-y-2 mb-8">
        <p class="text-xs uppercase tracking-wider text-emerald-700 font-semibold">Campagne de recrutement</p>
        <h2 class="text-2xl font-semibold text-gray-900">{{ $campaign->title }}</h2>
        @if ($campaign->description)
            <p class="text-sm text-gray-600">{{ $campaign->description }}</p>
        @endif
        <p class="text-sm text-gray-600">
            Lien valable jusqu'au <strong>{{ $token->expires_at->format('d/m/Y à H:i') }}</strong>.
        </p>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white p-6 mb-6">
        <h3 class="text-base font-semibold mb-4 text-gray-900">A. Informations issues d'iHRIS (non modifiables)</h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-gray-500">Matricule</dt>
                <dd class="font-medium">{{ $agent->matricule }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Prénom</dt>
                <dd class="font-medium">{{ $agent->first_name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Nom</dt>
                <dd class="font-medium">{{ $agent->last_name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Genre</dt>
                <dd class="font-medium">{{ $agent->gender ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Date de naissance</dt>
                <dd class="font-medium">{{ $agent->birth_date?->format('d/m/Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Catégorie</dt>
                <dd class="font-medium">{{ $agent->category ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Statut</dt>
                <dd class="font-medium">{{ $agent->agent_status ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Type de contrat</dt>
                <dd class="font-medium">{{ $agent->contract_type ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Employeur</dt>
                <dd class="font-medium">{{ $agent->employer ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Entrée dans la santé (iHRIS)</dt>
                <dd class="font-medium">{{ $agent->entry_date?->format('d/m/Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Structure (iHRIS)</dt>
                <dd class="font-medium">{{ $agent->structure ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">District</dt>
                <dd class="font-medium">{{ $agent->district ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Région</dt>
                <dd class="font-medium">{{ $agent->region ?? '—' }}</dd>
            </div>
        </dl>
    </section>

    <form
        action="{{ route('candidate.save', ['token' => $token->token]) }}"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-6"
    >
        @csrf

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="text-base font-semibold mb-4 text-gray-900">B. Mon dossier de candidature</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="block sm:col-span-2">
                    <span class="text-sm font-medium text-gray-700">
                        Poste choisi
                        @if ($isLocked)
                            <span class="ml-2 text-xs text-gray-500">(verrouillé après soumission)</span>
                        @endif
                    </span>
                    <select
                        name="position_id"
                        @if ($isLocked) disabled @endif
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none disabled:bg-gray-100"
                    >
                        <option value="">— Sélectionnez un poste —</option>
                        @foreach ($positions as $option)
                            <option value="{{ $option->id }}" @selected($selectedPositionId == $option->id)>
                                {{ $option->title }}
                            </option>
                        @endforeach
                    </select>
                    @if ($isLocked)
                        <input type="hidden" name="position_id" value="{{ $submission->position_id }}">
                    @endif
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Structure actuelle</span>
                    <input
                        type="text"
                        name="current_structure"
                        value="{{ old('current_structure', $submission?->current_structure ?? $agent->structure) }}"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                    >
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Service actuel</span>
                    <input
                        type="text"
                        name="current_service"
                        value="{{ old('current_service', $submission?->current_service ?? $agent->service) }}"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                    >
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Date d'entrée dans le système de santé</span>
                    <input
                        type="date"
                        name="service_entry_date"
                        value="{{ $serviceEntryDate }}"
                        min="1950-01-01"
                        max="{{ now()->format('Y-m-d') }}"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                    >
                    @if ($submission?->seniority_years !== null)
                        <span class="mt-1 inline-block text-xs text-gray-500">
                            Ancienneté calculée : {{ $submission->seniority_years }} {{ $submission->seniority_years > 1 ? 'années' : 'année' }}
                        </span>
                    @endif
                </label>
            </div>

            <label class="block mt-4">
                <span class="text-sm font-medium text-gray-700">Note de motivation (optionnel)</span>
                <textarea
                    name="motivation_note"
                    rows="5"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                >{{ old('motivation_note', $submission?->motivation_note) }}</textarea>
            </label>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="text-base font-semibold mb-4 text-gray-900">C. Curriculum vitae</h3>

            @if ($submission?->cv_path)
                <p class="text-sm text-gray-600 mb-2">CV déjà téléversé. Vous pouvez le remplacer ci-dessous.</p>
            @endif

            <label class="block">
                <span class="text-sm font-medium text-gray-700">Fichier PDF (max {{ $maxMb }} Mo)</span>
                <input
                    type="file"
                    name="cv"
                    accept="application/pdf"
                    class="mt-1 block w-full text-sm"
                >
            </label>
        </section>

        <div class="flex justify-end">
            <button
                type="submit"
                class="rounded-md bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
            >
                Enregistrer mon dossier
            </button>
        </div>
    </form>

    <section class="rounded-lg border border-gray-200 bg-white p-6 mt-8">
        <h3 class="text-base font-semibold mb-4 text-gray-900">D. Diplômes</h3>

        @if ($submission?->diplomas?->isNotEmpty())
            <ul class="divide-y divide-gray-200 mb-6">
                @foreach ($submission->diplomas as $diploma)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ $diploma->title }}</p>
                            <p class="text-gray-500">
                                {{ $diploma->institution ?? '—' }}
                                @if ($diploma->year) — {{ $diploma->year }} @endif
                            </p>
                        </div>
                        <form
                            action="{{ route('candidate.diploma.remove', ['token' => $token->token, 'diploma' => $diploma->id]) }}"
                            method="POST"
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">Supprimer</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-500 mb-6">Aucun diplôme enregistré pour le moment.</p>
        @endif

        <form
            action="{{ route('candidate.diploma.add', ['token' => $token->token]) }}"
            method="POST"
            enctype="multipart/form-data"
            class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-gray-200 pt-4"
        >
            @csrf
            <label class="block">
                <span class="text-sm font-medium text-gray-700">Intitulé du diplôme</span>
                <input type="text" name="title" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </label>
            <label class="block">
                <span class="text-sm font-medium text-gray-700">Établissement</span>
                <input type="text" name="institution" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </label>
            <label class="block">
                <span class="text-sm font-medium text-gray-700">Année d'obtention</span>
                <input type="number" name="year" min="1950" max="{{ (int) date('Y') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </label>
            <label class="block">
                <span class="text-sm font-medium text-gray-700">Fichier PDF (max {{ $maxMb }} Mo)</span>
                <input type="file" name="file" accept="application/pdf" required class="mt-1 block w-full text-sm">
            </label>
            <div class="sm:col-span-2 flex justify-end">
                <button type="submit" class="rounded-md bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">
                    Ajouter ce diplôme
                </button>
            </div>
        </form>
    </section>
@endsection
