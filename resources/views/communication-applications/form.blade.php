@extends('layouts.candidate')

@section('title', 'Candidature — Cellule de communication')

@section('content')
    <header class="mb-8 border-b border-gray-200 pb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Appel à candidatures interne · MSHP</p>
        <h1 class="mt-2 text-2xl font-semibold text-gray-900 sm:text-3xl">Rejoindre la Cellule de communication</h1>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-gray-600">
            Dans le cadre de la restructuration et du renforcement de sa Cellule de communication,
            le MSHP invite ses agents fonctionnaires et contractuels à présenter leur candidature.
        </p>
        <p class="mt-4 text-sm font-semibold text-emerald-800">Date limite : 13 novembre 2026 à 23 h 59, heure de Dakar.</p>
    </header>

    @if (session('communication_application_submitted'))
        <section class="rounded-lg border border-emerald-200 bg-white p-6 sm:p-8" aria-labelledby="confirmation-title">
            <h2 id="confirmation-title" class="text-xl font-semibold text-gray-900">Votre dossier a bien été reçu</h2>
            <p class="mt-3 text-sm leading-6 text-gray-600">Votre candidature et votre CV ont été transmis à l’équipe de recrutement de la Cellule de communication.</p>
            <p class="mt-2 text-sm leading-6 text-gray-600">Un seul dossier est accepté par adresse e-mail et par poste.</p>
            @if ($isOpen)
                <a href="{{ route('communication-applications.form') }}" class="mt-6 inline-block font-semibold text-emerald-800 underline">Déposer pour un autre poste</a>
            @endif
        </section>
    @elseif (! $isOpen)
        <section class="rounded-lg border border-gray-200 bg-white p-6 sm:p-8">
            <h2 class="text-xl font-semibold text-gray-900">Cet appel à candidatures est clos</h2>
            <p class="mt-3 text-sm leading-6 text-gray-600">Les dépôts ont pris fin le 13 novembre 2026 à 23 h 59, heure de Dakar. Les dossiers déjà déposés restent conservés.</p>
        </section>
    @else
        @if ($uploadError)
            <p role="alert" class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                Le fichier envoyé dépasse la limite autorisée. Veuillez joindre un CV PDF de {{ $maxUploadLabel }} maximum, puis renvoyer le formulaire.
            </p>
        @endif

        @php($inputClass = 'mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-base text-gray-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600')

        <p class="mb-4 text-sm text-gray-600">Tous les champs sont obligatoires. Préparez votre CV au format PDF ({{ $maxUploadLabel }} maximum).</p>

        <form id="communication-application-form" action="{{ route('communication-applications.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <section class="rounded-lg border border-gray-200 bg-white p-5 sm:p-6" aria-labelledby="position-title">
                <h2 id="position-title" class="mb-4 text-lg font-semibold text-gray-900">1. Le poste souhaité</h2>
                <label for="position" class="block text-sm font-medium text-gray-700">Poste</label>
                <select id="position" name="position" required class="{{ $inputClass }}" aria-describedby="position-help @error('position') position-error @enderror" @error('position') aria-invalid="true" @enderror>
                    <option value="">Sélectionnez un poste</option>
                    @foreach ($positions as $key => $position)
                        <option value="{{ $key }}" @selected(old('position') === $key)>{{ $position['title'] }}</option>
                    @endforeach
                </select>
                <p id="position-help" class="mt-2 text-sm text-gray-500">Un poste par dossier. Vous pouvez déposer séparément pour un autre poste.</p>
                @error('position') <p id="position-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror

                <div class="mt-5 space-y-3">
                    @foreach ($positions as $key => $position)
                        <details data-position-description="{{ $key }}" class="rounded-md border border-emerald-100 bg-emerald-50/50 p-4">
                            <summary class="cursor-pointer text-sm font-semibold text-emerald-900">Fiche du poste : {{ $position['title'] }}</summary>
                            <div class="mt-4 space-y-4 text-sm leading-6 text-gray-700">
                                <div><h3 class="font-semibold text-gray-900">Mission</h3><p>{{ $position['mission'] }}</p></div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Principales responsabilités</h3>
                                    <ul class="list-disc space-y-1 pl-5">
                                        @foreach ($position['responsibilities'] as $responsibility)
                                            <li>{{ $responsibility }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div><h3 class="font-semibold text-gray-900">Formation</h3><p>{{ $position['training'] }}</p></div>
                                <div><h3 class="font-semibold text-gray-900">Expérience</h3><p>{{ $position['experience'] }}</p></div>
                                @if ($position['skills'])
                                    <div><h3 class="font-semibold text-gray-900">Compétences techniques</h3><p>{{ $position['skills'] }}</p></div>
                                @endif
                                <div><h3 class="font-semibold text-gray-900">Qualités professionnelles</h3><p>Rigueur, discrétion, sens du service, organisation, disponibilité et aptitude au travail en équipe.</p></div>
                            </div>
                        </details>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 sm:p-6" aria-labelledby="candidate-title">
                <h2 id="candidate-title" class="mb-4 text-lg font-semibold text-gray-900">2. Vos informations</h2>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    @foreach ([
                        'first_name' => ['Prénom', 'text', 'given-name', 255],
                        'last_name' => ['Nom', 'text', 'family-name', 255],
                        'email' => ['E-mail', 'email', 'email', 255],
                        'phone' => ['Téléphone', 'tel', 'tel', 50],
                    ] as $field => [$label, $type, $autocomplete, $maxlength])
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" maxlength="{{ $maxlength }}" value="{{ old($field) }}" required class="{{ $inputClass }}" @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                            @error($field) <p id="{{ $field }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                    <div>
                        <label for="administrative_status" class="block text-sm font-medium text-gray-700">Statut administratif</label>
                        <select id="administrative_status" name="administrative_status" required class="{{ $inputClass }}" @error('administrative_status') aria-invalid="true" aria-describedby="administrative_status-error" @enderror>
                            <option value="">Sélectionnez votre statut</option>
                            @foreach ($administrativeStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('administrative_status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('administrative_status') <p id="administrative_status-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="current_structure" class="block text-sm font-medium text-gray-700">Structure d’affectation</label>
                        <input id="current_structure" name="current_structure" type="text" autocomplete="organization" maxlength="255" value="{{ old('current_structure') }}" required class="{{ $inputClass }}" @error('current_structure') aria-invalid="true" aria-describedby="current_structure-error" @enderror>
                        @error('current_structure') <p id="current_structure-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 sm:p-6" aria-labelledby="application-title">
                <h2 id="application-title" class="mb-4 text-lg font-semibold text-gray-900">3. Votre candidature</h2>
                <label for="motivation" class="block text-sm font-medium text-gray-700">Motivation</label>
                <p id="motivation-help" class="mt-1 text-sm leading-6 text-gray-500">Présentez votre motivation pour ce poste et vos expériences pertinentes. Ce texte remplace la lettre de motivation.</p>
                <textarea id="motivation" name="motivation" rows="7" maxlength="10000" required class="{{ $inputClass }}" aria-describedby="motivation-help @error('motivation') motivation-error @enderror" @error('motivation') aria-invalid="true" @enderror>{{ old('motivation') }}</textarea>
                @error('motivation') <p id="motivation-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror

                <div class="mt-6">
                    <label for="cv" class="block text-sm font-medium text-gray-700">Curriculum vitae actualisé</label>
                    <p id="cv-help" class="mt-1 text-sm text-gray-500">Un fichier PDF de {{ $maxUploadLabel }} maximum. Après une erreur, sélectionnez à nouveau votre fichier.</p>
                    <input id="cv" name="cv" type="file" accept=".pdf,application/pdf" required data-max-bytes="{{ $maxUploadBytes }}" class="mt-3 block w-full min-w-0 rounded-md border border-gray-300 p-2 text-sm text-gray-700 file:mr-3 file:rounded file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100" aria-describedby="cv-help @error('cv') cv-error @enderror" @error('cv') aria-invalid="true" @enderror>
                    @error('cv') <p id="cv-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </section>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="max-w-xl text-xs leading-5 text-gray-500">Vérifiez vos informations avant l’envoi. Un seul dossier est accepté par adresse e-mail et par poste.</p>
                <button type="submit" class="shrink-0 rounded-md bg-emerald-700 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:opacity-60">Déposer ma candidature</button>
            </div>
        </form>

        <script>
            (() => {
                const form = document.getElementById('communication-application-form');
                const positionSelect = document.getElementById('position');
                const descriptions = [...document.querySelectorAll('[data-position-description]')];
                const cvInput = document.getElementById('cv');

                const refreshDescription = () => {
                    descriptions.forEach((description) => {
                        description.hidden = description.dataset.positionDescription !== positionSelect.value;
                        description.open = !description.hidden;
                    });
                };

                positionSelect.addEventListener('change', refreshDescription);
                refreshDescription();

                cvInput.addEventListener('change', () => {
                    const tooLarge = cvInput.files[0]?.size > Number(cvInput.dataset.maxBytes);
                    cvInput.setCustomValidity(tooLarge ? 'Le CV dépasse la taille maximale indiquée. Veuillez choisir un fichier moins volumineux.' : '');
                    if (tooLarge) cvInput.reportValidity();
                });

                form.addEventListener('submit', () => {
                    const button = form.querySelector('button[type="submit"]');
                    button.disabled = true;
                    button.textContent = 'Envoi en cours…';
                });

                document.getElementById('form-error-summary')?.focus();
            })();
        </script>
    @endif
@endsection
