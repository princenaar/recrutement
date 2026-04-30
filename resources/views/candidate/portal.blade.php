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
    $selectedPosition = $positions->firstWhere('id', (int) $selectedPositionId);
    $hasDiplomas = $submission?->diplomas?->isNotEmpty() === true;
    $newDiplomaRows = old('new_diplomas', $hasDiplomas ? [] : [['title' => '', 'institution' => '', 'year' => '']]);
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
        <h3 class="text-base font-semibold mb-4 text-gray-900">A. Informations candidat non modifiables</h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-gray-500">Matricule</dt>
                <dd class="font-medium">{{ $agent->matricule }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Nom du candidat</dt>
                <dd class="font-medium">{{ trim($agent->first_name.' '.$agent->last_name) }}</dd>
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
                <dt class="text-gray-500">Date d'entrée dans le système de santé</dt>
                <dd class="font-medium">{{ $agent->entry_date?->format('d/m/Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Structure de référence</dt>
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
        id="candidate-submission-form"
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
                        Poste choisi <span class="text-red-600">*</span>
                        @if ($isLocked)
                            <span class="ml-2 text-xs text-gray-500">(verrouillé après soumission)</span>
                        @endif
                    </span>
                    <select
                        name="position_id"
                        @if ($isLocked) disabled @endif
                        @unless ($isLocked) required @endunless
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none disabled:bg-gray-100"
                    >
                        <option value="">— Sélectionnez un poste —</option>
                        @foreach ($positions as $option)
                            <option
                                value="{{ $option->id }}"
                                data-required-profile="{{ $option->required_profile }}"
                                @selected($selectedPositionId == $option->id)
                            >
                                {{ $option->title }}
                            </option>
                        @endforeach
                    </select>
                    <div
                        id="position-required-profile"
                        class="{{ $selectedPosition?->required_profile ? '' : 'hidden ' }}mt-3 rounded-md border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-gray-700"
                    >
                        <p class="font-medium text-emerald-900">Profil requis</p>
                        <p id="position-required-profile-text" class="mt-1 whitespace-pre-line">{{ $selectedPosition?->required_profile }}</p>
                    </div>
                    @if ($isLocked)
                        <input type="hidden" name="position_id" value="{{ $submission->position_id }}">
                    @endif
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Structure actuelle</span>
                    <span class="text-red-600">*</span>
                    <input
                        type="text"
                        name="current_structure"
                        value="{{ old('current_structure', $submission?->current_structure ?? $agent->structure) }}"
                        required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                    >
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Service actuel</span>
                    <span class="text-red-600">*</span>
                    <input
                        type="text"
                        name="current_service"
                        value="{{ old('current_service', $submission?->current_service ?? $agent->service) }}"
                        required
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
                @if (! $submission?->cv_path)
                    <span class="text-red-600">*</span>
                @endif
                <input
                    type="file"
                    name="cv"
                    accept="application/pdf"
                    @if (! $submission?->cv_path) required @endif
                    class="mt-2 block w-full cursor-pointer rounded-md border border-dashed border-gray-300 bg-gray-50 text-sm text-gray-600 file:mr-4 file:cursor-pointer file:border-0 file:bg-gray-800 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:border-emerald-400 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                >
                <span class="mt-2 block text-xs text-gray-500">Sélectionnez un CV au format PDF.</span>
            </label>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="text-base font-semibold mb-4 text-gray-900">D. Diplômes</h3>

            @if ($hasDiplomas)
                <ul class="divide-y divide-gray-200 mb-6">
                    @foreach ($submission->diplomas as $diploma)
                        <li class="py-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-medium">{{ $diploma->title }}</p>
                                    <p class="text-gray-500">
                                        {{ $diploma->institution ?? '—' }}
                                        @if ($diploma->year) — {{ $diploma->year }} @endif
                                    </p>
                                </div>
                                <label class="inline-flex items-center gap-2 text-sm text-red-700">
                                    <input
                                        type="checkbox"
                                        name="diplomas_to_delete[]"
                                        value="{{ $diploma->id }}"
                                        class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                    >
                                    Supprimer
                                </label>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-500 mb-6">Aucun diplôme enregistré pour le moment. Ajoutez au moins un diplôme avant d’enregistrer votre dossier.</p>
            @endif

            <div class="border-t border-gray-200 pt-4">
                <div id="new-diplomas" class="space-y-4">
                    @foreach ($newDiplomaRows as $index => $diploma)
                        <div class="new-diploma-row rounded-md border border-gray-200 bg-gray-50 p-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label class="block">
                                    <span class="text-sm font-medium text-gray-700">Intitulé du diplôme</span>
                                    <span class="text-red-600">*</span>
                                    <input
                                        type="text"
                                        name="new_diplomas[{{ $index }}][title]"
                                        value="{{ $diploma['title'] ?? '' }}"
                                        required
                                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                    >
                                </label>
                                <label class="block">
                                    <span class="text-sm font-medium text-gray-700">Établissement</span>
                                    <input
                                        type="text"
                                        name="new_diplomas[{{ $index }}][institution]"
                                        value="{{ $diploma['institution'] ?? '' }}"
                                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                    >
                                </label>
                                <label class="block">
                                    <span class="text-sm font-medium text-gray-700">Année d'obtention</span>
                                    <input
                                        type="number"
                                        name="new_diplomas[{{ $index }}][year]"
                                        value="{{ $diploma['year'] ?? '' }}"
                                        min="1950"
                                        max="{{ (int) date('Y') }}"
                                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                    >
                                </label>
                                <label class="block">
                                    <span class="text-sm font-medium text-gray-700">Fichier PDF (max {{ $maxMb }} Mo)</span>
                                    <span class="text-red-600">*</span>
                                    <input
                                        type="file"
                                        name="new_diplomas[{{ $index }}][file]"
                                        accept="application/pdf"
                                        required
                                        class="mt-2 block w-full cursor-pointer rounded-md border border-dashed border-gray-300 bg-white text-sm text-gray-600 file:mr-4 file:cursor-pointer file:border-0 file:bg-gray-800 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:border-emerald-400 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                    >
                                </label>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="button" class="remove-diploma-row text-sm text-red-600 hover:text-red-800">
                                    Retirer cette ligne
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button
                    type="button"
                    id="add-diploma-row"
                    class="mt-4 rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900"
                >
                    Ajouter un diplôme
                </button>
            </div>
        </section>

        <div class="mt-8 flex justify-end">
            <button
                type="submit"
                class="rounded-md bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
            >
                Enregistrer mon dossier
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const positionSelect = document.querySelector('select[name="position_id"]');
            const profilePanel = document.getElementById('position-required-profile');
            const profileText = document.getElementById('position-required-profile-text');
            const newDiplomas = document.getElementById('new-diplomas');
            const addDiplomaRow = document.getElementById('add-diploma-row');
            let diplomaIndex = {{ count($newDiplomaRows) }};

            if (! positionSelect || ! profilePanel || ! profileText) {
                return;
            }

            const refreshRequiredProfile = () => {
                const selectedOption = positionSelect.selectedOptions[0];
                const requiredProfile = selectedOption?.dataset.requiredProfile?.trim() ?? '';

                profileText.textContent = requiredProfile;
                profilePanel.classList.toggle('hidden', requiredProfile === '');
            };

            positionSelect.addEventListener('change', refreshRequiredProfile);
            refreshRequiredProfile();

            const bindRemoveButtons = () => {
                document.querySelectorAll('.remove-diploma-row').forEach((button) => {
                    button.addEventListener('click', () => {
                        button.closest('.new-diploma-row')?.remove();
                    });
                });
            };

            addDiplomaRow?.addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'new-diploma-row rounded-md border border-gray-200 bg-gray-50 p-4';
                row.innerHTML = `
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">Intitulé du diplôme</span>
                            <span class="text-red-600">*</span>
                            <input type="text" name="new_diplomas[${diplomaIndex}][title]" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">Établissement</span>
                            <input type="text" name="new_diplomas[${diplomaIndex}][institution]" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">Année d'obtention</span>
                            <input type="number" name="new_diplomas[${diplomaIndex}][year]" min="1950" max="{{ (int) date('Y') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">Fichier PDF (max {{ $maxMb }} Mo)</span>
                            <span class="text-red-600">*</span>
                            <input type="file" name="new_diplomas[${diplomaIndex}][file]" accept="application/pdf" required class="mt-2 block w-full cursor-pointer rounded-md border border-dashed border-gray-300 bg-white text-sm text-gray-600 file:mr-4 file:cursor-pointer file:border-0 file:bg-gray-800 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:border-emerald-400 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        </label>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="button" class="remove-diploma-row text-sm text-red-600 hover:text-red-800">Retirer cette ligne</button>
                    </div>
                `;

                newDiplomas?.appendChild(row);
                diplomaIndex++;
                bindRemoveButtons();
            });

            bindRemoveButtons();
        });
    </script>
@endsection
