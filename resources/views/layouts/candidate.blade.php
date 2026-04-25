<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Portail candidat') — Recrutement MSHP</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen antialiased flex flex-col">
    <header class="bg-white border-b-4 border-emerald-700 shadow-sm">
        <div class="max-w-5xl mx-auto px-6 py-5 flex items-center gap-6">
            <img
                src="{{ asset('images/senegal-flag.png') }}"
                alt="Drapeau du Sénégal"
                class="h-20 w-auto shrink-0 rounded-sm shadow-sm"
            >

            <div class="flex-1 text-center leading-tight">
                <p class="text-sm sm:text-base font-bold tracking-wide uppercase text-gray-900">
                    République du Sénégal
                </p>
                <p class="text-xs sm:text-sm italic text-emerald-800 mt-0.5">
                    Un Peuple — Un But — Une Foi
                </p>
                <p class="mt-2 text-xs sm:text-sm font-semibold uppercase tracking-wide text-gray-800">
                    Ministère de la Santé et de l'Hygiène Publique
                </p>
                <p class="text-xs uppercase tracking-wide text-gray-600">
                    Direction des Ressources Humaines
                </p>
                <p class="mt-2 text-xs text-gray-500">
                    Portail de recrutement interne
                </p>
            </div>

            <img
                src="{{ asset('images/logo_mshp.png') }}"
                alt="Ministère de la Santé et de l'Hygiène Publique"
                class="h-20 w-auto shrink-0"
            >
        </div>
    </header>

    <main class="flex-1 max-w-5xl w-full mx-auto px-6 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-medium mb-1">Veuillez corriger les erreurs suivantes :</p>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-gray-200 bg-white">
        <div class="max-w-5xl mx-auto px-6 py-6 text-xs text-gray-600 flex flex-col sm:flex-row items-center justify-between gap-2">
            <p>
                © {{ now()->year }} DRH — Ministère de la Santé et de l'Hygiène Publique. Vos données sont confidentielles et utilisées uniquement dans le cadre du recrutement interne.
            </p>
            <p class="text-gray-400">République du Sénégal</p>
        </div>
    </footer>
</body>
</html>
