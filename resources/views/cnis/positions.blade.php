@extends('layouts.candidate')

@section('title', 'Choix des postes CNIS')

@php
    $selectedInterestStatus = old('interest_status');
    $selectedChoices = [
        'first_choice' => old('first_choice'),
        'second_choice' => old('second_choice'),
        'third_choice' => old('third_choice'),
    ];
@endphp

@section('content')
    <section class="space-y-2 mb-8">
        <p class="text-xs uppercase tracking-wider text-emerald-700 font-semibold">Centre National d'Intelligence Sanitaire</p>
        <h2 class="text-2xl font-semibold text-gray-900">Choix des postes CNIS</h2>
        <p class="text-sm text-gray-600">
            Indiquez les postes qui vous intéressent, dans l'ordre de préférence. Vous pouvez choisir entre 1 et 3 postes, ou indiquer explicitement que vous n'êtes pas intéressé.
        </p>
    </section>

    <form action="{{ route('cnis.positions.store') }}" method="POST" class="space-y-6">
        @csrf

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="text-base font-semibold mb-4 text-gray-900">Informations candidat</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Prénom <span class="text-red-600">*</span></span>
                    <input
                        type="text"
                        name="first_name"
                        value="{{ old('first_name') }}"
                        required
                        autocomplete="given-name"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                    >
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Nom <span class="text-red-600">*</span></span>
                    <input
                        type="text"
                        name="last_name"
                        value="{{ old('last_name') }}"
                        required
                        autocomplete="family-name"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                    >
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="text-base font-semibold mb-4 text-gray-900">Intérêt pour les postes</h3>

            <div class="space-y-3">
                <label class="flex items-start gap-3 rounded-md border border-gray-200 px-4 py-3 text-sm">
                    <input
                        type="radio"
                        name="interest_status"
                        value="interested"
                        @checked($selectedInterestStatus === 'interested')
                        required
                        class="mt-1 border-gray-300 text-emerald-700 focus:ring-emerald-500"
                    >
                    <span>
                        <span class="block font-medium text-gray-900">Je suis intéressé par un ou plusieurs postes</span>
                        <span class="text-gray-600">Classez jusqu'à 3 postes par ordre de préférence.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-md border border-gray-200 px-4 py-3 text-sm">
                    <input
                        type="radio"
                        name="interest_status"
                        value="not_interested"
                        @checked($selectedInterestStatus === 'not_interested')
                        required
                        class="mt-1 border-gray-300 text-emerald-700 focus:ring-emerald-500"
                    >
                    <span>
                        <span class="block font-medium text-gray-900">Je ne suis pas intéressé</span>
                        <span class="text-gray-600">Cette réponse sera enregistrée sans choix de poste.</span>
                    </span>
                </label>
            </div>
        </section>

        <section id="choice-section" class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="text-base font-semibold mb-2 text-gray-900">Classement des postes</h3>
            <p class="mb-4 text-sm text-gray-600">Le premier choix est obligatoire si vous êtes intéressé. Les deuxième et troisième choix sont optionnels.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ([
                    'first_choice' => 'Choix 1',
                    'second_choice' => 'Choix 2',
                    'third_choice' => 'Choix 3',
                ] as $field => $label)
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">
                            {{ $label }}
                            @if ($field === 'first_choice')
                                <span class="text-red-600">*</span>
                            @endif
                        </span>
                        <select
                            name="{{ $field }}"
                            data-choice-select
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none disabled:bg-gray-100"
                        >
                            <option value="">— Aucun —</option>
                            @foreach ($positions as $key => $position)
                                <option value="{{ $key }}" @selected($selectedChoices[$field] === $key)>{{ $position['title'] }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="text-base font-semibold mb-4 text-gray-900">Descriptions des postes</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach ($positions as $key => $position)
                    <div class="rounded-md border border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <h4 class="text-sm font-semibold text-gray-900">{{ $position['title'] }}</h4>
                            <button
                                type="button"
                                data-modal-open="cnis-position-{{ $loop->index }}"
                                class="shrink-0 rounded-md border border-emerald-200 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            >
                                Voir la description
                            </button>
                        </div>

                        <dialog
                            id="cnis-position-{{ $loop->index }}"
                            class="m-auto w-[min(48rem,calc(100%-2rem))] max-h-[85vh] rounded-lg border border-gray-200 p-0 shadow-xl backdrop:bg-gray-950/50"
                        >
                            <div class="sticky top-0 flex items-center justify-between gap-4 border-b border-gray-200 bg-white px-5 py-4">
                                <h5 class="text-base font-semibold text-gray-900">{{ $position['title'] }}</h5>
                                <button
                                    type="button"
                                    data-modal-close
                                    class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                >
                                    Fermer
                                </button>
                            </div>

                            <div class="max-h-[calc(85vh-4.5rem)] overflow-y-auto px-5 py-4 text-sm text-gray-700 [&_h2]:mt-5 [&_h2:first-child]:mt-0 [&_h2]:text-base [&_h2]:font-semibold [&_h2]:text-gray-950 [&_h3]:mt-4 [&_h3]:font-semibold [&_h3]:text-gray-900 [&_p]:mt-2 [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5 [&_strong]:font-semibold">
                                {!! \Illuminate\Support\Str::markdown($position['description']) !!}
                            </div>
                        </dialog>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex justify-end">
            <button
                type="submit"
                class="rounded-md bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
            >
                Enregistrer ma réponse
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const choiceSection = document.getElementById('choice-section');
            const choiceSelects = [...document.querySelectorAll('[data-choice-select]')];
            const interestInputs = [...document.querySelectorAll('input[name="interest_status"]')];

            const refreshChoices = () => {
                const notInterested = document.querySelector('input[name="interest_status"]:checked')?.value === 'not_interested';
                choiceSection?.classList.toggle('opacity-50', notInterested);

                choiceSelects.forEach((select) => {
                    select.disabled = notInterested;

                    if (notInterested) {
                        select.value = '';
                    }
                });

                refreshDistinctOptions();
            };

            const refreshDistinctOptions = () => {
                const selectedValues = choiceSelects
                    .map((select) => select.value)
                    .filter(Boolean);

                choiceSelects.forEach((select) => {
                    [...select.options].forEach((option) => {
                        if (! option.value) {
                            option.disabled = false;

                            return;
                        }

                        option.disabled = option.value !== select.value && selectedValues.includes(option.value);
                    });
                });
            };

            interestInputs.forEach((input) => input.addEventListener('change', refreshChoices));
            choiceSelects.forEach((select) => select.addEventListener('change', refreshDistinctOptions));

            document.querySelectorAll('[data-modal-open]').forEach((button) => {
                button.addEventListener('click', () => {
                    document.getElementById(button.dataset.modalOpen)?.showModal();
                });
            });

            document.querySelectorAll('[data-modal-close]').forEach((button) => {
                button.addEventListener('click', () => {
                    button.closest('dialog')?.close();
                });
            });

            refreshChoices();
        });
    </script>
@endsection
