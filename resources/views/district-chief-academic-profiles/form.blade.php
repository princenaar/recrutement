@extends('layouts.candidate')

@section('title', 'Informations académiques des médecins chefs de district')

@php
    $oldDiplomas = old('diplomas', [
        ['name' => '', 'obtained_year' => ''],
    ]);

    if ($oldDiplomas === []) {
        $oldDiplomas = [
            ['name' => '', 'obtained_year' => ''],
        ];
    }

    $maxYear = now()->year;
@endphp

@section('content')
    <section class="space-y-2 mb-8">
        <p class="text-xs uppercase tracking-wider text-emerald-700 font-semibold">Médecins chefs de district</p>
        <h2 class="text-2xl font-semibold text-gray-900">Informations académiques</h2>
        <p class="text-sm text-gray-600">
            Renseignez votre identité, votre date de prise de service et les diplômes obtenus. Le certificat d'inscription est demandé uniquement si vous suivez une formation en cours.
        </p>
    </section>

    <form action="{{ route('district-chief-academic-profiles.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="text-base font-semibold mb-4 text-gray-900">Informations personnelles</h3>

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

                <label class="block sm:col-span-2">
                    <span class="text-sm font-medium text-gray-700">Date de prise de service <span class="text-red-600">*</span></span>
                    <input
                        type="date"
                        name="service_start_date"
                        value="{{ old('service_start_date') }}"
                        required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                    >
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Diplômes obtenus</h3>
                    <p class="mt-1 text-sm text-gray-600">Ajoutez au moins un diplôme. Le scan peut être au format PDF, PNG, JPG ou JPEG.</p>
                </div>

                <button
                    type="button"
                    id="add-diploma"
                    class="rounded-md border border-emerald-200 bg-white px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
                    Ajouter un diplôme
                </button>
            </div>

            <div id="diplomas-list" class="mt-5 space-y-4">
                @foreach ($oldDiplomas as $index => $diploma)
                    <div class="rounded-md border border-gray-200 bg-gray-50 p-4" data-diploma-row>
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h4 class="text-sm font-semibold text-gray-900">Diplôme <span data-diploma-number>{{ $loop->iteration }}</span></h4>
                            <button
                                type="button"
                                class="rounded-md border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500"
                                data-remove-diploma
                            >
                                Retirer
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">Nom du diplôme <span class="text-red-600">*</span></span>
                                <input
                                    type="text"
                                    name="diplomas[{{ $index }}][name]"
                                    value="{{ $diploma['name'] ?? '' }}"
                                    required
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                                >
                            </label>

                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">Année d'obtention <span class="text-red-600">*</span></span>
                                <input
                                    type="number"
                                    name="diplomas[{{ $index }}][obtained_year]"
                                    value="{{ $diploma['obtained_year'] ?? '' }}"
                                    required
                                    min="1900"
                                    max="{{ $maxYear }}"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                                >
                            </label>

                            <label class="block sm:col-span-2">
                                <span class="text-sm font-medium text-gray-700">Scan du diplôme <span class="text-red-600">*</span></span>
                                <input
                                    type="file"
                                    name="diplomas[{{ $index }}][scan]"
                                    required
                                    accept="application/pdf,image/png,image/jpeg"
                                    class="mt-1 block w-full cursor-pointer rounded-md border border-dashed border-gray-300 bg-white text-sm text-gray-600 file:mr-4 file:cursor-pointer file:border-0 file:bg-gray-800 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:border-emerald-400 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                >
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="text-base font-semibold mb-2 text-gray-900">Formation en cours</h3>
            <p class="mb-4 text-sm text-gray-600">Si vous êtes inscrit à une formation en cours, joignez le certificat d'inscription correspondant.</p>

            <label class="block">
                <span class="text-sm font-medium text-gray-700">Certificat d'inscription</span>
                <input
                    type="file"
                    name="training_certificate"
                    accept="application/pdf,image/png,image/jpeg"
                    class="mt-1 block w-full cursor-pointer rounded-md border border-dashed border-gray-300 bg-white text-sm text-gray-600 file:mr-4 file:cursor-pointer file:border-0 file:bg-gray-800 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:border-emerald-400 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                >
            </label>
        </section>

        <div class="flex justify-end">
            <button
                type="submit"
                class="rounded-md bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
            >
                Enregistrer mes informations
            </button>
        </div>
    </form>

    <template id="diploma-template">
        <div class="rounded-md border border-gray-200 bg-gray-50 p-4" data-diploma-row>
            <div class="mb-4 flex items-center justify-between gap-3">
                <h4 class="text-sm font-semibold text-gray-900">Diplôme <span data-diploma-number></span></h4>
                <button
                    type="button"
                    class="rounded-md border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500"
                    data-remove-diploma
                >
                    Retirer
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Nom du diplôme <span class="text-red-600">*</span></span>
                    <input
                        type="text"
                        data-name-field="name"
                        required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                    >
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Année d'obtention <span class="text-red-600">*</span></span>
                    <input
                        type="number"
                        data-name-field="obtained_year"
                        required
                        min="1900"
                        max="{{ $maxYear }}"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                    >
                </label>

                <label class="block sm:col-span-2">
                    <span class="text-sm font-medium text-gray-700">Scan du diplôme <span class="text-red-600">*</span></span>
                    <input
                        type="file"
                        data-name-field="scan"
                        required
                        accept="application/pdf,image/png,image/jpeg"
                        class="mt-1 block w-full cursor-pointer rounded-md border border-dashed border-gray-300 bg-white text-sm text-gray-600 file:mr-4 file:cursor-pointer file:border-0 file:bg-gray-800 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:border-emerald-400 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                    >
                </label>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const list = document.getElementById('diplomas-list');
            const template = document.getElementById('diploma-template');
            const addButton = document.getElementById('add-diploma');
            let nextIndex = {{ count($oldDiplomas) }};

            const refreshDiplomas = () => {
                const rows = [...list.querySelectorAll('[data-diploma-row]')];

                rows.forEach((row, position) => {
                    row.querySelector('[data-diploma-number]').textContent = position + 1;
                    row.querySelector('[data-remove-diploma]').disabled = rows.length === 1;
                    row.querySelector('[data-remove-diploma]').classList.toggle('opacity-50', rows.length === 1);
                    row.querySelector('[data-remove-diploma]').classList.toggle('cursor-not-allowed', rows.length === 1);
                });
            };

            const wireRow = (row) => {
                row.querySelector('[data-remove-diploma]').addEventListener('click', () => {
                    if (list.querySelectorAll('[data-diploma-row]').length === 1) {
                        return;
                    }

                    row.remove();
                    refreshDiplomas();
                });
            };

            list.querySelectorAll('[data-diploma-row]').forEach(wireRow);

            addButton.addEventListener('click', () => {
                const fragment = template.content.cloneNode(true);
                const row = fragment.querySelector('[data-diploma-row]');

                row.querySelectorAll('[data-name-field]').forEach((input) => {
                    input.name = `diplomas[${nextIndex}][${input.dataset.nameField}]`;
                });

                nextIndex += 1;
                wireRow(row);
                list.appendChild(fragment);
                refreshDiplomas();
            });

            refreshDiplomas();
        });
    </script>
@endsection
