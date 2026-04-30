@extends('layouts.candidate')

@section('title', 'Ma candidature')

@php
    $responses = $submission?->responses ?? [];
    $selectedPositionId = old('position_id', $submission?->position_id);
    $selectedPosition = $positions->firstWhere('id', (int) $selectedPositionId);
    $isLocked = $submission?->submitted_at !== null;
    $selectedRegions = old('region_choices', $submission?->region_choices ?? []);
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
        <h3 class="text-base font-semibold mb-4 text-gray-900">A. Informations candidat (non modifiables)</h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-gray-500">Identifiant CNI/Passeport</dt>
                <dd class="font-medium">{{ $agent->matricule }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Nom</dt>
                <dd class="font-medium">{{ $agent->full_name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Date de naissance</dt>
                <dd class="font-medium">{{ $agent->birth_date?->format('d/m/Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Téléphone</dt>
                <dd class="font-medium">{{ $agent->phone ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Email</dt>
                <dd class="font-medium">{{ $agent->email ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Diplôme / niveau</dt>
                <dd class="font-medium">{{ trim(($agent->category ?? '').' / '.($agent->current_position ?? ''), ' /') ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Lieu de résidence</dt>
                <dd class="font-medium">{{ $agent->structure ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Région souhaitée initiale</dt>
                <dd class="font-medium">{{ $agent->region ?? '—' }}</dd>
            </div>
        </dl>
    </section>

    <form id="criteria-submission-form" action="{{ route('candidate.save', ['token' => $token->token]) }}" method="POST" class="space-y-6">
        @csrf

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="text-base font-semibold mb-4 text-gray-900">B. Poste et situation actuelle</h3>

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
                    <span class="text-sm font-medium text-gray-700">Êtes-vous actuellement en activité ?</span>
                    <span class="text-red-600">*</span>
                    <select
                        id="currently-active"
                        name="currently_active"
                        required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                    >
                        <option value="">— Choisir —</option>
                        <option value="yes" @selected(old('currently_active', $responses['currently_active'] ?? '') === 'yes')>Oui</option>
                        <option value="no" @selected(old('currently_active', $responses['currently_active'] ?? '') === 'no')>Non</option>
                    </select>
                </label>

                <label id="activity-location-wrapper" class="block">
                    <span class="text-sm font-medium text-gray-700">Où exercez-vous actuellement ?</span>
                    <span class="text-red-600">*</span>
                    <input
                        id="activity-location"
                        type="text"
                        name="activity_location"
                        value="{{ old('activity_location', $responses['activity_location'] ?? '') }}"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                    >
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="text-base font-semibold mb-4 text-gray-900">C. Critères de candidature</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Niveau du diplôme et adéquation santé/données</span>
                    <span class="text-red-600">*</span>
                    <select name="degree_level" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="">— Choisir —</option>
                        <option value="master_data_health" @selected(old('degree_level', $responses['degree_level'] ?? '') === 'master_data_health')>Master adapté santé/données</option>
                        <option value="licence_data_health" @selected(old('degree_level', $responses['degree_level'] ?? '') === 'licence_data_health')>Licence adaptée santé/données</option>
                        <option value="other_relevant" @selected(old('degree_level', $responses['degree_level'] ?? '') === 'other_relevant')>Autre diplôme pertinent</option>
                        <option value="not_relevant" @selected(old('degree_level', $responses['degree_level'] ?? '') === 'not_relevant')>Non pertinent</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Années d’expérience en données sanitaires/service santé</span>
                    <span class="text-red-600">*</span>
                    <input
                        type="number"
                        name="experience_years"
                        value="{{ old('experience_years', $responses['experience_years'] ?? 0) }}"
                        min="0"
                        max="50"
                        required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                    >
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Connaissance du SNIS et des outils de rapportage</span>
                    <span class="text-red-600">*</span>
                    <select name="knows_snis" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="">— Choisir —</option>
                        <option value="yes" @selected(old('knows_snis', $responses['knows_snis'] ?? '') === 'yes')>Oui</option>
                        <option value="no" @selected(old('knows_snis', $responses['knows_snis'] ?? '') === 'no')>Non</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Connaissance pratique du DHIS2</span>
                    <span class="text-red-600">*</span>
                    <select name="dhis2_level" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="">— Choisir —</option>
                        <option value="advanced" @selected(old('dhis2_level', $responses['dhis2_level'] ?? '') === 'advanced')>Bonne maîtrise</option>
                        <option value="basic" @selected(old('dhis2_level', $responses['dhis2_level'] ?? '') === 'basic')>Notions pratiques</option>
                        <option value="none" @selected(old('dhis2_level', $responses['dhis2_level'] ?? '') === 'none')>Aucune</option>
                    </select>
                </label>

                <label class="block sm:col-span-2">
                    <span class="text-sm font-medium text-gray-700">Bonne maîtrise informatique : Excel, PowerPoint, R ou équivalent</span>
                    <span class="text-red-600">*</span>
                    <select name="computer_skills" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="">— Choisir —</option>
                        <option value="yes" @selected(old('computer_skills', $responses['computer_skills'] ?? '') === 'yes')>Oui</option>
                        <option value="no" @selected(old('computer_skills', $responses['computer_skills'] ?? '') === 'no')>Non</option>
                    </select>
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="text-base font-semibold mb-2 text-gray-900">D. Motivation terrain</h3>
            <p class="mb-4 text-sm text-gray-600">Choisissez au maximum 3 régions d’affectation acceptées.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($regions as $region)
                    <label class="flex items-center justify-between gap-3 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                        <span class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="region_choices[]"
                                value="{{ $region }}"
                                @checked(in_array($region, $selectedRegions, true))
                                class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-500"
                            >
                            {{ $region }}
                        </span>
                        <span class="text-xs font-medium text-gray-500">{{ $regionPoints[$region] }} pt</span>
                    </label>
                @endforeach
            </div>

            <label class="block mt-4">
                <span class="text-sm font-medium text-gray-700">Note de motivation (optionnel)</span>
                <textarea
                    name="motivation_note"
                    rows="5"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                >{{ old('motivation_note', $responses['motivation_note'] ?? '') }}</textarea>
            </label>
        </section>

        @if ($submission?->normalized_score !== null)
            <section class="rounded-lg border border-emerald-200 bg-emerald-50 p-6 text-sm text-emerald-950">
                Score calculé : <strong>{{ $submission->normalized_score }}/100</strong>
                <span class="text-emerald-800">({{ $submission->raw_score }}/65 points bruts)</span>
            </section>
        @endif

        <div class="mt-8 flex justify-end">
            <button
                type="submit"
                class="rounded-md bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
            >
                Enregistrer ma candidature
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const activeSelect = document.getElementById('currently-active');
            const locationWrapper = document.getElementById('activity-location-wrapper');
            const locationInput = document.getElementById('activity-location');
            const positionSelect = document.querySelector('select[name="position_id"]');
            const profilePanel = document.getElementById('position-required-profile');
            const profileText = document.getElementById('position-required-profile-text');
            const regionInputs = document.querySelectorAll('input[name="region_choices[]"]');

            const refreshActivityLocation = () => {
                const isActive = activeSelect?.value === 'yes';
                locationWrapper?.classList.toggle('hidden', ! isActive);
                if (locationInput) {
                    locationInput.required = isActive;
                    if (! isActive) {
                        locationInput.value = '';
                    }
                }
            };

            const refreshRequiredProfile = () => {
                const requiredProfile = positionSelect?.selectedOptions[0]?.dataset.requiredProfile?.trim() ?? '';
                if (profileText) {
                    profileText.textContent = requiredProfile;
                }
                profilePanel?.classList.toggle('hidden', requiredProfile === '');
            };

            const enforceRegionLimit = () => {
                const checked = document.querySelectorAll('input[name="region_choices[]"]:checked').length;
                regionInputs.forEach((input) => {
                    input.disabled = ! input.checked && checked >= 3;
                });
            };

            activeSelect?.addEventListener('change', refreshActivityLocation);
            positionSelect?.addEventListener('change', refreshRequiredProfile);
            regionInputs.forEach((input) => input.addEventListener('change', enforceRegionLimit));

            refreshActivityLocation();
            refreshRequiredProfile();
            enforceRegionLimit();
        });
    </script>
@endsection
