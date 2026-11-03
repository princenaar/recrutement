<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php($cvUrl = route('admin.files.communication-application.cv', ['application' => $getRecord()]))

    <div>
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <a href="{{ $cvUrl }}" target="_blank" rel="noopener noreferrer" style="text-decoration: underline; font-weight: 600;">
                Ouvrir le CV
            </a>
            <a href="{{ $cvUrl }}?download=1" style="text-decoration: underline; font-weight: 600;">
                Télécharger le CV
            </a>
        </div>
        <iframe
            src="{{ $cvUrl }}"
            title="CV de {{ $getRecord()->full_name }}"
            loading="lazy"
            style="display: block; width: 100%; height: 75vh; min-height: 400px; border: 1px solid #d1d5db; border-radius: 0.5rem;"
        ></iframe>
        <p style="margin-top: 0.75rem; font-size: 0.875rem;">
            Si l’aperçu ne s’affiche pas, utilisez « Ouvrir le CV » ou « Télécharger le CV ».
        </p>
    </div>
</x-dynamic-component>
