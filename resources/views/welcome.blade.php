@extends('layouts.candidate')

@section('title', 'Accueil')

@section('content')
    <style>
        @keyframes home-rise {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes home-scan {
            0%,
            100% {
                transform: translateY(-18%);
                opacity: .35;
            }

            50% {
                transform: translateY(128%);
                opacity: .7;
            }
        }

        .home-rise {
            animation: home-rise .7s ease-out both;
        }

        .home-scan {
            animation: home-scan 5.5s ease-in-out infinite;
        }
    </style>

    <section class="relative left-1/2 w-screen -translate-x-1/2 overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_72%_28%,rgba(16,185,129,.25),transparent_30%),linear-gradient(135deg,rgba(15,23,42,.15),rgba(15,23,42,.88))]"></div>
        <div class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-gray-50 to-transparent"></div>

        <div class="relative mx-auto grid min-h-[calc(100svh-10rem)] max-w-6xl items-center gap-12 px-6 py-16 lg:grid-cols-[1.05fr_.95fr] lg:py-20">
            <div class="max-w-2xl home-rise">
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-emerald-200">
                    Direction des Ressources Humaines
                </p>
                <h1 class="mt-5 text-4xl font-black leading-[1.02] text-white sm:text-6xl lg:text-7xl">
                    Recrutement interne MSHP
                </h1>
                <p class="mt-6 max-w-xl text-base leading-8 text-slate-200 sm:text-lg">
                    Un portail sécurisé pour déposer les candidatures des agents du Ministère de la Santé et de l'Hygiène Publique.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a
                        href="#fonctionnement"
                        class="inline-flex items-center justify-center rounded-md bg-emerald-400 px-5 py-3 text-sm font-bold text-slate-950 transition hover:-translate-y-0.5 hover:bg-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:ring-offset-2 focus:ring-offset-slate-950"
                    >
                        Comprendre le parcours
                    </a>
                    <a
                        href="{{ url('/admin') }}"
                        class="inline-flex items-center justify-center rounded-md border border-white/20 px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-slate-950"
                    >
                        Back-office DRH
                    </a>
                </div>
            </div>

            <div class="home-rise relative mx-auto w-full max-w-md [animation-delay:160ms]">
                <div class="absolute -inset-6 rounded-full bg-emerald-300/10 blur-3xl"></div>
                <div class="relative overflow-hidden rounded-[2rem] border border-white/15 bg-white/10 p-5 shadow-2xl shadow-slate-950/40 backdrop-blur">
                    <div class="flex items-center justify-between border-b border-white/10 pb-4">
                        <div class="flex items-center gap-3">
                            <img
                                src="{{ asset('images/logo_mshp.png') }}"
                                alt="Ministère de la Santé et de l'Hygiène Publique"
                                class="h-12 w-12 rounded-md bg-white object-contain p-1"
                            >
                            <div>
                                <p class="text-sm font-bold">Dossier candidat</p>
                                <p class="text-xs text-slate-300">Lien personnel actif temporairement</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-emerald-300 px-3 py-1 text-xs font-bold text-slate-950">Sécurisé</span>
                    </div>

                    <div class="relative mt-5 overflow-hidden rounded-2xl bg-slate-900/80 p-5">
                        <div class="home-scan absolute left-0 right-0 top-0 h-16 bg-gradient-to-b from-emerald-200/0 via-emerald-200/35 to-emerald-200/0"></div>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs uppercase tracking-[.22em] text-slate-400">Identité iHRIS</p>
                                <div class="mt-3 grid grid-cols-2 gap-3">
                                    <span class="h-3 rounded-full bg-white/80"></span>
                                    <span class="h-3 rounded-full bg-white/50"></span>
                                    <span class="h-3 rounded-full bg-white/35"></span>
                                    <span class="h-3 rounded-full bg-white/60"></span>
                                </div>
                            </div>

                            <div class="rounded-xl border border-emerald-200/20 bg-emerald-200/10 p-4">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold text-emerald-100">Documents joints</p>
                                    <p class="text-xs text-emerald-100">PDF privé</p>
                                </div>
                                <div class="mt-4 space-y-3">
                                    <div class="flex items-center gap-3">
                                        <span class="h-8 w-8 rounded-md bg-emerald-300/90"></span>
                                        <span class="h-2 flex-1 rounded-full bg-white/55"></span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="h-8 w-8 rounded-md bg-white/25"></span>
                                        <span class="h-2 flex-1 rounded-full bg-white/35"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between rounded-xl bg-white px-4 py-3 text-slate-950">
                                <span class="text-sm font-bold">Soumission enregistrée</span>
                                <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="fonctionnement" class="py-16">
        <div class="grid gap-10 lg:grid-cols-[.8fr_1.2fr] lg:items-start">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-emerald-700">Accès sur invitation</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                    Le parcours reste simple, confidentiel et traçable.
                </h2>
            </div>

            <div class="divide-y divide-slate-200 border-y border-slate-200">
                <article class="grid gap-4 py-6 sm:grid-cols-[4rem_1fr]">
                    <p class="text-3xl font-black text-emerald-700">01</p>
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">Recevoir le lien personnel</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            La DRH transmet un lien unique par e-mail, SMS ou WhatsApp selon les informations disponibles.
                        </p>
                    </div>
                </article>

                <article class="grid gap-4 py-6 sm:grid-cols-[4rem_1fr]">
                    <p class="text-3xl font-black text-emerald-700">02</p>
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">Compléter le dossier</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Les données iHRIS sont affichées en lecture seule ; l'agent ajoute ses informations actualisées, son CV et ses diplômes.
                        </p>
                    </div>
                </article>

                <article class="grid gap-4 py-6 sm:grid-cols-[4rem_1fr]">
                    <p class="text-3xl font-black text-emerald-700">03</p>
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">Mettre à jour avant expiration</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Le dossier peut être modifié tant que le lien est actif. Les documents restent stockés dans un espace privé.
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="relative left-1/2 w-screen -translate-x-1/2 bg-white">
        <div class="mx-auto grid max-w-6xl gap-10 px-6 py-16 lg:grid-cols-3">
            <div class="lg:col-span-1">
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-emerald-700">Garanties</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">
                    Conçu pour un recrutement interne maîtrisé.
                </h2>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:col-span-2">
                <div class="border-l-2 border-emerald-600 pl-5">
                    <h3 class="font-bold text-slate-950">Données iHRIS protégées</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">
                        Les informations importées servent de référence et ne sont pas modifiables depuis le portail.
                    </p>
                </div>
                <div class="border-l-2 border-emerald-600 pl-5">
                    <h3 class="font-bold text-slate-950">Fichiers privés</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">
                        CV et diplômes ne sont jamais exposés via un lien public direct.
                    </p>
                </div>
                <div class="border-l-2 border-emerald-600 pl-5">
                    <h3 class="font-bold text-slate-950">Suivi recruteur</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">
                        Les candidatures sont centralisées pour la revue, la présélection et le rejet motivé.
                    </p>
                </div>
                <div class="border-l-2 border-emerald-600 pl-5">
                    <h3 class="font-bold text-slate-950">Double canal</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">
                        Les invitations fonctionnent par e-mail ou par message manuel quand l'adresse est absente.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-14">
        <div class="flex flex-col gap-6 border-y border-slate-200 py-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-emerald-700">Besoin d'aide ?</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Vous n'avez pas reçu votre lien personnel ?</h2>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                    Contactez la Direction des Ressources Humaines pour vérifier votre éligibilité aux campagnes en cours.
                </p>
            </div>
            <a
                href="{{ url('/admin') }}"
                class="inline-flex shrink-0 items-center justify-center rounded-md bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
            >
                Accès administrateur
            </a>
        </div>
    </section>
@endsection
