@extends('layouts.candidate')

@section('title', 'Informations académiques des médecins chefs de district')

@php
    $uploadLimits = $uploadLimits ?? [
        'max_file_kb' => (int) config('recrutement.upload_max_size_kb'),
        'max_file_bytes' => (int) config('recrutement.upload_max_size_kb') * 1024,
        'max_file_label' => ((int) config('recrutement.upload_max_size_kb') / 1024).' Mo',
        'max_total_upload_bytes' => null,
        'max_total_upload_label' => null,
        'compression_url' => 'https://www.ilovepdf.com/compress_pdf',
        'allowed_extensions' => ['pdf', 'png', 'jpg', 'jpeg'],
        'allowed_mime_types' => ['application/pdf', 'image/png', 'image/jpeg'],
    ];

    $oldDiplomas = old('diplomas', [
        ['name' => '', 'obtained_year' => ''],
    ]);

    if ($oldDiplomas === []) {
        $oldDiplomas = [
            ['name' => '', 'obtained_year' => ''],
        ];
    }

    $maxYear = now()->year;
    $maxFileSizeBytes = (int) $uploadLimits['max_file_bytes'];
    $maxFileSizeLabel = (string) $uploadLimits['max_file_label'];
    $maxTotalUploadBytes = $uploadLimits['max_total_upload_bytes'];
    $maxTotalUploadLabel = $uploadLimits['max_total_upload_label'];
    $compressionUrl = (string) $uploadLimits['compression_url'];
    $uploadError = $uploadError ?? null;
    $allowedExtensions = $uploadLimits['allowed_extensions'];
    $allowedMimeTypes = $uploadLimits['allowed_mime_types'];

    $textInputBase = 'mt-1 block w-full rounded-md border px-3 py-2 text-sm focus:outline-none';
    $fileInputBase = 'mt-1 block w-full cursor-pointer rounded-md border border-dashed bg-white text-sm text-gray-600 file:mr-4 file:cursor-pointer file:border-0 file:bg-gray-800 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:border-emerald-400 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-offset-2';

    $inputClasses = function (string $field, string $base) use ($errors): string {
        return $base.' '.($errors->has($field)
            ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
            : 'border-gray-300 focus:border-emerald-500 focus:ring-emerald-500');
    };

    $errorId = fn (string $field): string => 'error-'.trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', $field), '-');

    $errorAttributes = function (string $field) use ($errors, $errorId): string {
        if (! $errors->has($field)) {
            return '';
        }

        return 'aria-invalid="true" aria-describedby="'.$errorId($field).'"';
    };

    $renderLinkedError = function (?string $message) use ($compressionUrl): string {
        if (! $message) {
            return '';
        }

        if (! str_contains($message, $compressionUrl)) {
            return e($message);
        }

        $parts = explode($compressionUrl, $message, 2);

        return e($parts[0]).'<a href="'.$compressionUrl.'" target="_blank" rel="noopener noreferrer" class="font-semibold underline">iLovePDF Compress PDF</a>'.e($parts[1] ?? '');
    };
@endphp

@section('content')
    <section class="space-y-2 mb-8">
        <p class="text-xs uppercase tracking-wider text-emerald-700 font-semibold">Médecins chefs de district</p>
        <h2 class="text-2xl font-semibold text-gray-900">Informations académiques</h2>
        <p class="text-sm text-gray-600">
            Renseignez votre identité, votre date de prise de service et les diplômes obtenus. Le certificat d'inscription est demandé uniquement si vous suivez une formation en cours.
        </p>
    </section>

    @if ($uploadError)
        <div id="form-error-summary" role="alert" tabindex="-1" class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 focus:outline-none focus:ring-2 focus:ring-red-500">
            <p class="font-medium mb-1">Veuillez corriger les erreurs suivantes :</p>
            <ul class="list-disc list-inside space-y-1">
                <li>{!! $renderLinkedError($uploadError) !!}</li>
            </ul>
        </div>
    @endif

    <form
        action="{{ route('district-chief-academic-profiles.store') }}"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-6"
        data-academic-profile-form
        novalidate
    >
        @csrf

        <div id="client-error-summary" role="alert" tabindex="-1" class="hidden rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 focus:outline-none focus:ring-2 focus:ring-red-500">
            <p class="font-medium">Veuillez corriger les erreurs suivantes :</p>
            <ul class="mt-2 list-disc list-inside space-y-1" data-client-error-list></ul>
        </div>

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
                        data-field-label="Prénom"
                        data-required-message="Le prénom est obligatoire."
                        class="{{ $inputClasses('first_name', $textInputBase) }}"
                        {!! $errorAttributes('first_name') !!}
                    >
                    @error('first_name')
                        <p id="{{ $errorId('first_name') }}" role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 hidden text-sm text-red-700" role="alert" data-client-error-for="first_name"></p>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Nom <span class="text-red-600">*</span></span>
                    <input
                        type="text"
                        name="last_name"
                        value="{{ old('last_name') }}"
                        required
                        autocomplete="family-name"
                        data-field-label="Nom"
                        data-required-message="Le nom est obligatoire."
                        class="{{ $inputClasses('last_name', $textInputBase) }}"
                        {!! $errorAttributes('last_name') !!}
                    >
                    @error('last_name')
                        <p id="{{ $errorId('last_name') }}" role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 hidden text-sm text-red-700" role="alert" data-client-error-for="last_name"></p>
                </label>

                <label class="block sm:col-span-2">
                    <span class="text-sm font-medium text-gray-700">Date de prise de service <span class="text-red-600">*</span></span>
                    <input
                        type="date"
                        name="service_start_date"
                        value="{{ old('service_start_date') }}"
                        required
                        data-field-label="Date de prise de service"
                        data-required-message="La date de prise de service est obligatoire."
                        class="{{ $inputClasses('service_start_date', $textInputBase) }}"
                        {!! $errorAttributes('service_start_date') !!}
                    >
                    @error('service_start_date')
                        <p id="{{ $errorId('service_start_date') }}" role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 hidden text-sm text-red-700" role="alert" data-client-error-for="service_start_date"></p>
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Diplômes obtenus</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Ajoutez au moins un diplôme. Le scan peut être au format PDF, PNG, JPG ou JPEG.
                        Chaque fichier doit faire {{ $maxFileSizeLabel }} maximum.
                        @if ($maxTotalUploadLabel)
                            La taille totale de l'envoi ne doit pas dépasser {{ $maxTotalUploadLabel }}.
                        @endif
                    </p>
                    @error('diplomas')
                        <p id="{{ $errorId('diplomas') }}" role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
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
                    @php
                        $nameField = "diplomas.{$index}.name";
                        $yearField = "diplomas.{$index}.obtained_year";
                        $scanField = "diplomas.{$index}.scan";
                    @endphp

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
                                    data-field-label="Nom du diplôme"
                                    data-required-message="Le nom du diplôme est obligatoire."
                                    class="{{ $inputClasses($nameField, $textInputBase) }}"
                                    {!! $errorAttributes($nameField) !!}
                                >
                                @error($nameField)
                                    <p id="{{ $errorId($nameField) }}" role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 hidden text-sm text-red-700" role="alert" data-client-error-for="diplomas[{{ $index }}][name]"></p>
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
                                    data-field-label="Année d'obtention"
                                    data-required-message="L'année d'obtention est obligatoire."
                                    class="{{ $inputClasses($yearField, $textInputBase) }}"
                                    {!! $errorAttributes($yearField) !!}
                                >
                                @error($yearField)
                                    <p id="{{ $errorId($yearField) }}" role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 hidden text-sm text-red-700" role="alert" data-client-error-for="diplomas[{{ $index }}][obtained_year]"></p>
                            </label>

                            <label class="block sm:col-span-2">
                                <span class="text-sm font-medium text-gray-700">Scan du diplôme <span class="text-red-600">*</span></span>
                                <input
                                    type="file"
                                    name="diplomas[{{ $index }}][scan]"
                                    required
                                    accept="application/pdf,image/png,image/jpeg"
                                    data-field-label="Scan du diplôme"
                                    data-required-message="Le scan du diplôme est obligatoire."
                                    class="{{ $inputClasses($scanField, $fileInputBase) }}"
                                    {!! $errorAttributes($scanField) !!}
                                >
                                <p class="mt-1 text-xs text-gray-500">Formats acceptés : PDF, PNG, JPG, JPEG. Taille maximum : {{ $maxFileSizeLabel }}.</p>
                                @error($scanField)
                                    <p id="{{ $errorId($scanField) }}" role="alert" class="mt-1 text-sm text-red-700">{!! $renderLinkedError($message) !!}</p>
                                @enderror
                                <p class="mt-1 hidden text-sm text-red-700" role="alert" data-client-error-for="diplomas[{{ $index }}][scan]"></p>
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
                    data-field-label="Certificat d'inscription"
                    class="{{ $inputClasses('training_certificate', $fileInputBase) }}"
                    {!! $errorAttributes('training_certificate') !!}
                >
                <p class="mt-1 text-xs text-gray-500">Formats acceptés : PDF, PNG, JPG, JPEG. Taille maximum : {{ $maxFileSizeLabel }}.</p>
                @error('training_certificate')
                    <p id="{{ $errorId('training_certificate') }}" role="alert" class="mt-1 text-sm text-red-700">{!! $renderLinkedError($message) !!}</p>
                @enderror
                <p class="mt-1 hidden text-sm text-red-700" role="alert" data-client-error-for="training_certificate"></p>
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
                        data-field-label="Nom du diplôme"
                        data-required-message="Le nom du diplôme est obligatoire."
                        class="{{ $inputClasses('diplomas.template.name', $textInputBase) }}"
                    >
                    <p class="mt-1 hidden text-sm text-red-700" role="alert" data-name-field-error="name"></p>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Année d'obtention <span class="text-red-600">*</span></span>
                    <input
                        type="number"
                        data-name-field="obtained_year"
                        required
                        min="1900"
                        max="{{ $maxYear }}"
                        data-field-label="Année d'obtention"
                        data-required-message="L'année d'obtention est obligatoire."
                        class="{{ $inputClasses('diplomas.template.obtained_year', $textInputBase) }}"
                    >
                    <p class="mt-1 hidden text-sm text-red-700" role="alert" data-name-field-error="obtained_year"></p>
                </label>

                <label class="block sm:col-span-2">
                    <span class="text-sm font-medium text-gray-700">Scan du diplôme <span class="text-red-600">*</span></span>
                    <input
                        type="file"
                        data-name-field="scan"
                        required
                        accept="application/pdf,image/png,image/jpeg"
                        data-field-label="Scan du diplôme"
                        data-required-message="Le scan du diplôme est obligatoire."
                        class="{{ $inputClasses('diplomas.template.scan', $fileInputBase) }}"
                    >
                    <p class="mt-1 text-xs text-gray-500">Formats acceptés : PDF, PNG, JPG, JPEG. Taille maximum : {{ $maxFileSizeLabel }}.</p>
                    <p class="mt-1 hidden text-sm text-red-700" role="alert" data-name-field-error="scan"></p>
                </label>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-academic-profile-form]');
            const list = document.getElementById('diplomas-list');
            const template = document.getElementById('diploma-template');
            const addButton = document.getElementById('add-diploma');
            const clientSummary = document.getElementById('client-error-summary');
            const clientErrorList = clientSummary?.querySelector('[data-client-error-list]');
            const serverSummary = document.getElementById('form-error-summary');
            const maxFileSizeBytes = @json($maxFileSizeBytes);
            const maxFileSizeLabel = @json($maxFileSizeLabel);
            const maxTotalUploadBytes = @json($maxTotalUploadBytes);
            const maxTotalUploadLabel = @json($maxTotalUploadLabel);
            const compressionUrl = @json($compressionUrl);
            const allowedExtensions = @json($allowedExtensions);
            const allowedMimeTypes = @json($allowedMimeTypes);
            let nextIndex = {{ count($oldDiplomas) }};

            const fileTooLargeMessage = (file) => {
                const extension = getExtension(file.name);
                const isPdf = extension === 'pdf' || file.type === 'application/pdf';
                const base = `Le fichier ne doit pas dépasser ${maxFileSizeLabel}.`;

                if (! isPdf) {
                    return base;
                }

                return `${base} Si votre PDF est trop volumineux, compressez-le avec <a href="${compressionUrl}" target="_blank" rel="noopener noreferrer" class="font-semibold underline">iLovePDF Compress PDF</a>.`;
            };

            const totalUploadMessage = () => {
                const limit = maxTotalUploadLabel ? ` de ${maxTotalUploadLabel}` : '';

                return `La taille totale des fichiers sélectionnés dépasse la limite autorisée${limit}. Retirez un fichier ou compressez vos PDF avec <a href="${compressionUrl}" target="_blank" rel="noopener noreferrer" class="font-semibold underline">iLovePDF Compress PDF</a>.`;
            };

            const getExtension = (name) => name.split('.').pop()?.toLowerCase() ?? '';

            const isAllowedFile = (file) => {
                const extension = getExtension(file.name);

                return allowedExtensions.includes(extension)
                    && (allowedMimeTypes.includes(file.type) || file.type === '');
            };

            const findClientError = (input) => {
                return [...form.querySelectorAll('[data-client-error-for]')]
                    .find((error) => error.dataset.clientErrorFor === input.name);
            };

            const setClientError = (input, message) => {
                const error = findClientError(input);

                if (! error) {
                    return;
                }

                if (message) {
                    if (! error.id) {
                        error.id = `client-error-${input.name.replace(/[^a-z0-9_-]+/gi, '-')}`;
                    }

                    error.innerHTML = message;
                    error.classList.remove('hidden');
                    input.setAttribute('aria-invalid', 'true');
                    input.setAttribute('aria-describedby', error.id);

                    return;
                }

                error.innerHTML = '';
                error.classList.add('hidden');

                if (! input.hasAttribute('data-server-invalid')) {
                    input.removeAttribute('aria-invalid');
                    input.removeAttribute('aria-describedby');
                }
            };

            const validateInput = (input) => {
                if (input.disabled) {
                    setClientError(input, '');

                    return null;
                }

                const label = input.dataset.fieldLabel ?? 'Ce champ';

                if (input.required) {
                    const isMissing = input.type === 'file'
                        ? input.files.length === 0
                        : input.value.trim() === '';

                    if (isMissing) {
                        const message = input.dataset.requiredMessage ?? `${label} est obligatoire.`;

                        setClientError(input, message);

                        return message;
                    }
                }

                if (input.type === 'number' && input.value !== '') {
                    const value = Number(input.value);
                    const min = input.min === '' ? null : Number(input.min);
                    const max = input.max === '' ? null : Number(input.max);

                    if (! Number.isInteger(value)) {
                        const message = `${label} doit être une année valide.`;
                        setClientError(input, message);

                        return message;
                    }

                    if (min !== null && value < min) {
                        const message = `${label} doit être supérieure ou égale à ${input.min}.`;
                        setClientError(input, message);

                        return message;
                    }

                    if (max !== null && value > max) {
                        const message = `${label} ne peut pas être dans le futur.`;
                        setClientError(input, message);

                        return message;
                    }
                }

                if (input.type === 'file' && input.files.length > 0) {
                    const file = input.files[0];

                    if (! isAllowedFile(file)) {
                        const message = `${label} doit être au format PDF, PNG, JPG ou JPEG.`;
                        setClientError(input, message);

                        return message;
                    }

                    if (file.size > maxFileSizeBytes) {
                        const message = fileTooLargeMessage(file);
                        setClientError(input, message);

                        return message;
                    }
                }

                setClientError(input, '');

                return null;
            };

            const selectedFiles = () => {
                return [...form.querySelectorAll('input[type="file"]')]
                    .flatMap((input) => [...input.files]);
            };

            const refreshSummary = (messages) => {
                if (! clientSummary || ! clientErrorList) {
                    return;
                }

                clientErrorList.innerHTML = '';

                if (messages.length === 0) {
                    clientSummary.classList.add('hidden');

                    return;
                }

                messages.forEach((message) => {
                    const item = document.createElement('li');
                    item.innerHTML = message;
                    clientErrorList.appendChild(item);
                });

                clientSummary.classList.remove('hidden');
                clientSummary.focus({ preventScroll: true });
                clientSummary.scrollIntoView({ behavior: 'auto', block: 'start' });
            };

            const validateForm = () => {
                const messages = [];

                form.querySelectorAll('input').forEach((input) => {
                    const message = validateInput(input);

                    if (message) {
                        messages.push(message);
                    }
                });

                if (maxTotalUploadBytes) {
                    const totalSize = selectedFiles().reduce((total, file) => total + file.size, 0);

                    if (totalSize > maxTotalUploadBytes) {
                        messages.push(totalUploadMessage());
                    }
                }

                refreshSummary([...new Set(messages)]);

                return messages.length === 0;
            };

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
                    validateForm();
                    refreshDiplomas();
                });

                row.querySelectorAll('input').forEach((input) => {
                    input.addEventListener('input', () => validateInput(input));
                    input.addEventListener('change', () => validateInput(input));
                });
            };

            form.querySelectorAll('[aria-invalid="true"]').forEach((input) => {
                input.setAttribute('data-server-invalid', 'true');
            });

            list.querySelectorAll('[data-diploma-row]').forEach(wireRow);

            addButton.addEventListener('click', () => {
                const fragment = template.content.cloneNode(true);
                const row = fragment.querySelector('[data-diploma-row]');

                row.querySelectorAll('[data-name-field]').forEach((input) => {
                    input.name = `diplomas[${nextIndex}][${input.dataset.nameField}]`;
                });

                row.querySelectorAll('[data-name-field-error]').forEach((error) => {
                    error.dataset.clientErrorFor = `diplomas[${nextIndex}][${error.dataset.nameFieldError}]`;
                    delete error.dataset.nameFieldError;
                });

                nextIndex += 1;
                wireRow(row);
                list.appendChild(fragment);
                refreshDiplomas();
            });

            [...form.querySelectorAll('input')]
                .filter((input) => ! input.closest('[data-diploma-row]'))
                .forEach((input) => {
                    input.addEventListener('input', () => validateInput(input));
                    input.addEventListener('change', () => validateInput(input));
                });

            form.addEventListener('submit', (event) => {
                if (! validateForm()) {
                    event.preventDefault();
                }
            });

            refreshDiplomas();

            if (serverSummary) {
                serverSummary.focus({ preventScroll: true });
                serverSummary.scrollIntoView({ behavior: 'auto', block: 'start' });
            }
        });
    </script>
@endsection
