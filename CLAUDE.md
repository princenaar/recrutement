# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# Application de Recrutement Interne MSHP

## État actuel du code

> **MVP livré.** Phases 0-9 du [`PLAN.md`](./PLAN.md) terminées : modèles + migrations + services métier (TDD) + portail candidat + back-office Filament v5.6 (4 ressources + actions dédiées) + seeder + tests end-to-end. 34 tests verts.
>
> Avant de modifier un fichier, **toujours vérifier son chemin réel** — Filament v5 split chaque ressource en sous-dossiers (`Pages/`, `Schemas/`, `Tables/`, `RelationManagers/`), ce qui diffère des chemins simplifiés évoqués dans ce document.

> **Plan d'implémentation :** [`PLAN.md`](./PLAN.md) (10 phases, smoke test end-to-end inclus).

## Commandes de développement

```bash
# Installation complète (après clone)
composer setup

# Dev (démarre serve + queue:listen + vite en parallèle via concurrently)
composer dev

# Tests (Pest)
composer test                                   # tous les tests
php artisan test --compact                      # sortie compacte
php artisan test --compact --filter=TestName    # un seul test

# Formatage PHP — OBLIGATOIRE avant de finaliser toute modif PHP
vendor/bin/pint --dirty --format agent

# Assets Vite / Tailwind v4
npm run dev     # watcher
npm run build   # build production

# Artisan — génération de fichiers (toujours utiliser make: plutôt que créer manuellement)
php artisan make:model Xxx -mfs       # modèle + migration + factory + seeder
php artisan make:test --pest Name     # test Pest
php artisan list                      # découvrir les commandes

# Inspection rapide
php artisan route:list --except-vendor
php artisan config:show database.default
```

**Windows :** la commande Python sur cette machine est `py` (pas `python`). Pour l'essentiel, tout se fait via `php artisan` / `composer` / `npm` — pas besoin de Python.

**Base de données :** MySQL (`DB_CONNECTION=mysql`, base `recrutement`). Pas SQLite malgré le seed script par défaut de Laravel.

## Vue d'ensemble du projet

Application web de gestion de recrutement interne pour le Ministère de la Santé et de l'Hygiène Publique (MSHP) du Sénégal. Les agents sont préchargés depuis iHRIS (fichier Excel) et invités via des liens temporaires individuels à soumettre leur dossier de candidature.

**Stack :** Laravel 13 · Filament 5 · MySQL · Tailwind CSS

---

## Architecture du domaine

### Entités principales

```
Campaign (campagne de recrutement)
  └── Position (poste à pourvoir, 1-n par campagne)
        └── Application (candidature d'un agent)
              ├── Agent (préchargé depuis iHRIS, non modifiable)
              ├── Submission (formulaire rempli par l'agent)
              │     ├── cv_path (PDF unique)
              │     └── Diploma[] (PDF multiples)
              └── InvitationToken (lien temporaire 7 jours)
```

### Modèles Eloquent

#### `Campaign`
```
id, title, description, status (draft|active|closed), starts_at, ends_at, created_at, updated_at
```

#### `Position`
```
id, campaign_id, title, description, required_profile, status (open|closed), created_at, updated_at
```

#### `Agent` (préchargé, JAMAIS modifié après import)
```
id
matricule              -- "Numéro d'identification" iHRIS
first_name             -- "Prénom"
last_name              -- "Nom"
gender                 -- "Sexe"
birth_date             -- "Date de naissance" (stocké en date, pas en numérique Excel)
nationality            -- "Nationalité"
email                  -- "Email personnnel" (peut être null → invitation manuelle)
phone                  -- "Numéro de téléphone mobile"
category               -- "Catégorie socio-professionnelle"
current_position       -- "Poste occupé (Fonction)"
position_start_date    -- "Date d'Occupation du Poste"
service                -- "Service"
structure              -- "Nom de la Structure"
district               -- "Districts/Hôpitaux"
region                 -- "Région"
employer               -- "Employeur"
contract_type          -- "Type de contrat"
agent_status           -- "Statut de l'agent" (Fonctionnaire / Contractuel / Non Fonctionnaire)
entry_date             -- "Date d'entrée dans le systeme de santé"
marital_status         -- "Situation matrimoniale"
ihris_imported_at      -- timestamp import
created_at, updated_at
```

> **Règle absolue :** Les colonnes de la table `agents` sont en lecture seule après import. Toute tentative d'édition depuis le back-office sur ces champs doit être bloquée.

#### `InvitationToken`
```
id
agent_id
position_id
token                  -- UUID v4 unique
expires_at             -- created_at + 7 jours
used_at                -- null tant que non soumis, timestamp à la 1ère soumission
notification_sent_at   -- timestamp envoi mail
notification_channel   -- email|manual
revoked_at             -- null ou timestamp si révoqué manuellement
created_at, updated_at
```

#### `Submission`
```
id
invitation_token_id
agent_id
position_id
-- Champs remplis par l'agent (modifiables pendant les 7 jours) :
current_structure      -- Structure actuelle (peut différer d'iHRIS si changement récent)
current_service        -- Service actuel
years_in_service       -- Ancienneté calculée ou saisie
motivation_note        -- Texte libre (optionnel)
cv_path                -- Chemin fichier PDF unique (storage/app/private/submissions/{token}/)
submitted_at           -- timestamp 1ère soumission
last_updated_at        -- timestamp dernière modification
status                 -- draft|submitted|under_review|shortlisted|rejected
shortlisted_at         -- timestamp présélection
shortlisted_by         -- user_id admin
rejection_note         -- Commentaire interne (non visible agent)
created_at, updated_at
```

#### `Diploma`
```
id
submission_id
title                  -- ex. "Licence Informatique", "Master MIAGE"
institution            -- ex. "UCAD"
year                   -- Année d'obtention
file_path              -- Chemin PDF (storage/app/private/submissions/{token}/diplomas/)
created_at, updated_at
```

---

## Structure des fichiers

> **MVP livré (Phases 0-9) — état réel du dépôt.** Filament v5.6 scaffolde par dossier (`Pages/`, `Schemas/`, `Tables/`, `RelationManagers/`) ; le ressource principal sur les candidatures s'appelle `SubmissionResource` (modèle `Submission`) mais est libellé **« Candidatures »** dans la nav.

```
app/
├── Models/
│   ├── Campaign.php · Position.php · Agent.php
│   ├── InvitationToken.php · Submission.php · Diploma.php
│   └── User.php
├── Enums/
│   ├── CampaignStatus.php · PositionStatus.php
│   ├── SubmissionStatus.php · InvitationChannel.php
├── Exceptions/
│   ├── ActiveInvitationExistsException.php
│   └── InvalidSubmissionFileException.php
├── Support/
│   └── ImportResult.php                              -- DTO retour AgentImportService
├── Services/
│   ├── AgentImportService.php                        -- Import Excel iHRIS (PhpSpreadsheet)
│   ├── InvitationService.php                         -- Tokens + envoi mail/manuel
│   └── SubmissionService.php                         -- Dossiers + fichiers privés
├── Notifications/
│   └── InvitationNotification.php                    -- Mail (ShouldQueue)
├── Http/Controllers/
│   ├── CandidatePortalController.php                 -- Routes publiques /candidature/{token}
│   └── Admin/FileDownloadController.php              -- Download CV + diplômes (admin)
├── Filament/
│   ├── Actions/                                      -- Actions Filament dédiées (Phase 7)
│   │   ├── ImportAgentsAction.php
│   │   ├── SendInvitationAction.php
│   │   ├── ShortlistAction.php
│   │   └── RejectAction.php
│   └── Resources/
│       ├── Campaigns/                                -- CRUD + RelationManagers/PositionsRelationManager
│       ├── Agents/                                   -- readonly (canCreate=false, pas d'Edit)
│       ├── Submissions/                              -- = « Candidatures » (List + ViewSubmission + Infolist)
│       └── InvitationTokens/                         -- = « Invitations » (suivi)
└── Providers/Filament/AdminPanelProvider.php

resources/views/
├── layouts/candidate.blade.php
└── candidate/
    ├── portal.blade.php · expired.blade.php
    ├── error.blade.php · submitted.blade.php

database/
├── migrations/                                        -- 6 migrations métier
├── factories/                                         -- factories Eloquent
└── seeders/
    ├── DatabaseSeeder.php                             -- admin + import + campagne + 5 tokens
    └── data/agents_ihris_sample.xlsx                  -- fixture iHRIS (5 lignes)

routes/web.php                                         -- /candidature/* (public) + /admin/files/* (auth)

tests/Feature/
├── AgentImportServiceTest.php · InvitationServiceTest.php
├── SubmissionServiceTest.php · CandidatePortalTest.php
└── RecrutementFlowTest.php                            -- end-to-end

tests/fixtures/agents_sample.xlsx                      -- fixture utilisée par les tests d'import
```

---

## Flux utilisateur détaillé

### Flux Admin (back-office Filament)

```
1. Créer une Campaign
2. Ajouter un ou plusieurs Positions à la campagne
3. Importer les agents via Excel (AgentImportService)
   → Dédoublonnage sur matricule
   → Conversion dates Excel (numérique → Carbon)
   → email null si colonne vide → flag "invitation manuelle"
4. Depuis ApplicationResource :
   → Sélectionner agent(s) + position → [Envoyer invitation]
   → InvitationService génère token UUID, expires_at = now()+7j
   → Si email présent : envoi mail automatique (InvitationNotification)
   → Si email absent : afficher modal avec texte copier-coller (SMS/WhatsApp)
5. Suivi : voir statut de chaque dossier (draft / submitted / shortlisted / rejected)
6. Présélection : bouton [Présélectionner] → status = shortlisted
   Rejet : bouton [Rejeter] avec note interne optionnelle
```

### Flux Agent (portail public)

```
URL : /candidature/{token}

1. Vérification token :
   - Invalide → 404
   - Révoqué → page erreur explicite
   - Expiré → page "expires.blade.php"
   - Valide → afficher le formulaire

2. Affichage formulaire :
   SECTION A — Informations iHRIS (READ ONLY, pré-remplies) :
   - Matricule, Prénom, Nom, Genre
   - Date de naissance
   - Catégorie socio-professionnelle
   - Statut agent / Type de contrat / Employeur
   - Date d'entrée dans la santé
   - Structure, District, Région (depuis iHRIS)

   SECTION B — Informations actualisables (éditables) :
   - Structure actuelle (pré-remplie depuis iHRIS, modifiable)
   - Service actuel (pré-rempli, modifiable)
   - Ancienneté dans le service (calculée auto si dates dispos, sinon saisie)
   - Note de motivation (textarea, optionnel)

   SECTION C — Documents :
   - CV (PDF unique, max 5 Mo) — remplace l'existant si re-soumis
   - Diplômes (PDF multiples, max 5 Mo chacun) — avec titre + établissement + année par fichier

3. L'agent peut revenir modifier SECTION B et SECTION C
   jusqu'à expiration du token (7 jours)
   → Chaque sauvegarde met à jour last_updated_at
   → submitted_at = timestamp de la 1ère soumission

4. Après soumission → page de confirmation avec résumé
```

---

## Détails d'implémentation critiques

### Import Excel iHRIS

```php
// AgentImportService::import(string $filePath): ImportResult
// - Utiliser maatwebsite/excel ou openpyxl-style via spatie/simple-excel
// - Les dates iHRIS sont stockées en numérique Excel (ex: 26484 = 1972-07-16)
//   Conversion : Carbon::createFromFormat('d/m/Y', Date::excelToDateTimeObject($value)->format('d/m/Y'))
//   OU : Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))
// - email null → pas de blocage, juste flag pour invitation manuelle
// - Dédoublonnage sur matricule → updateOrCreate(['matricule' => ...], [...])
// - Retourner ImportResult avec nb créés, mis à jour, ignorés
```

### Génération et validation des tokens

```php
// InvitationService::createToken(Agent $agent, Position $position): InvitationToken
// - token = Str::uuid()
// - expires_at = now()->addDays(7)
// - URL publique : route('candidate.portal', ['token' => $token->token])

// Middleware ou vérification dans controller :
// 1. Token existe → sinon abort(404)
// 2. Token révoqué → page erreur
// 3. Token expiré → vue candidate.expired
// 4. Valide → continuer
```

### Gestion des fichiers

```php
// Storage disk : 'private' (storage/app/private/)
// Structure : submissions/{token_uuid}/cv.pdf
//             submissions/{token_uuid}/diplomas/{uuid}.pdf
// 
// JAMAIS stocker dans public/ → les fichiers candidats sont confidentiels
// Accès admin via Storage::disk('private')->download(...)
// Validation : mimes:pdf · max:5120 (5 Mo)
// 
// Remplacement CV : supprimer l'ancien avant de stocker le nouveau
// Remplacement diplôme : le diplôme est lié à son enregistrement Diploma,
//   supprimer le fichier orphelin avant update
```

### Notification — double canal

```php
// InvitationNotification implémente ShouldQueue
// Si agent->email présent :
//   → Mail::to($agent->email)->send(new InvitationMail($token))
//   → invitation_token->notification_channel = 'email'
//   → invitation_token->notification_sent_at = now()
//
// Si agent->email null :
//   → NE PAS envoyer de mail
//   → Retourner le texte du message formaté pour copier-coller
//   → invitation_token->notification_channel = 'manual'
//   → L'admin copie le message et l'envoie par SMS/WhatsApp
//
// Le texte du message manuel doit inclure :
//   - Prénom Nom de l'agent
//   - Nom du poste
//   - URL complète avec token
//   - Date d'expiration (J+7)
```

### Présélection (ShortlistAction)

```php
// Filament BulkAction ou Action sur ligne
// → submission->status = 'shortlisted'
// → submission->shortlisted_at = now()
// → submission->shortlisted_by = auth()->id()
// Pas de notification agent à ce stade (à confirmer ultérieurement)
// 
// RejectAction identique avec status = 'rejected'
// + modal pour saisir rejection_note (optionnel)
```

---

## Back-office Filament — ressources et vues

### CampaignResource
- ListCampaigns : table avec status badge, nombre de postes, nombre de candidatures
- CreateCampaign / EditCampaign : formulaire + section RelationManager Positions
- PositionsRelationManager : gestion des postes inline

### AgentResource
- ListAgents : table readonly avec colonnes : Matricule · Prénom · Nom · Structure · Catégorie · Email · Statut invitation
- Action : [Importer Excel] → modal upload → AgentImportService → résumé
- Action par ligne : [Inviter pour un poste] → sélection campagne + poste → InvitationService
- Filtre : par structure, par statut token (invité / non invité / soumis)

### ApplicationResource (vue principale recruteur)
- Table : Candidat · Poste · Campagne · Date soumission · Statut · Actions
- Filtres : campagne, poste, statut (tous / soumis / présélectionnés / rejetés)
- Vue détail : affiche toutes les données agent + submission + liens téléchargement CV/diplômes
- Actions : [Présélectionner] · [Rejeter] · [Voir CV] · [Voir Diplômes] · [Régénérer lien]

### InvitationTokenResource (optionnel, vue de suivi)
- Table : Agent · Poste · Expiré le · Canal · Statut (actif/expiré/révoqué)
- Action : [Révoquer] · [Copier lien] · [Renvoyer mail]

---

## Configuration requise

### `.env` (variables spécifiques projet)

```env
# Mail (SMTP ou log en dev)
MAIL_MAILER=smtp
MAIL_HOST=your.smtp.host
MAIL_PORT=587
MAIL_USERNAME=noreply@sante.gouv.sn
MAIL_PASSWORD=secret
MAIL_FROM_ADDRESS=noreply@sante.gouv.sn
MAIL_FROM_NAME="Recrutement MSHP"

# URL publique (pour les liens token dans les mails)
APP_URL=https://recrutement.e-drhsante.com

# Durée validité token en jours
INVITATION_TOKEN_VALIDITY_DAYS=7

# Taille max upload fichiers (en Ko)
UPLOAD_MAX_SIZE_KB=5120
```

### `config/filesystems.php`
S'assurer que le disk `private` existe et pointe vers `storage/app/private` avec visibilité `private`.

---

## Packages requis

```bash
composer require filament/filament:"^5.0"
composer require maatwebsite/excel          # Import Excel
composer require spatie/laravel-medialibrary # Optionnel si gestion avancée fichiers
# OU utiliser le Storage natif Laravel (recommandé pour simplicité)

# PhpSpreadsheet (inclus dans maatwebsite/excel) gère la conversion des dates Excel
```

---

## Migrations (ordre de création)

```
1. create_campaigns_table
2. create_positions_table
3. create_agents_table
4. create_invitation_tokens_table
5. create_submissions_table
6. create_diplomas_table
```

---

## Routes publiques (portail candidat)

```php
// routes/web.php
Route::prefix('candidature')->name('candidate.')->group(function () {
    Route::get('/{token}', [CandidatePortalController::class, 'show'])->name('portal');
    Route::post('/{token}', [CandidatePortalController::class, 'save'])->name('save');
    Route::post('/{token}/diploma', [CandidatePortalController::class, 'addDiploma'])->name('diploma.add');
    Route::delete('/{token}/diploma/{diploma}', [CandidatePortalController::class, 'removeDiploma'])->name('diploma.remove');
});
```

> Aucun middleware `auth` sur ces routes. La sécurité repose uniquement sur l'UUID du token.

---

## Règles métier à respecter strictement

1. **Un token par agent par poste.** Si un token actif existe, ne pas en créer un nouveau — proposer de le régénérer (ce qui révoque l'ancien).
2. **Les données iHRIS ne sont jamais modifiables** par l'agent ni par l'admin depuis le back-office (sauf ré-import Excel complet qui fait `updateOrCreate`).
3. **Les fichiers sont privés.** Aucun lien direct vers `storage/`. Toujours passer par un contrôleur authentifié (admin) ou par le token (agent).
4. **L'ancienneté** est calculée automatiquement si `position_start_date` est disponible dans iHRIS (`now()->diffInYears($agent->position_start_date)`). Si la date est nulle, l'agent la saisit manuellement.
5. **Token expiré ≠ dossier perdu.** La `Submission` reste accessible en lecture pour l'admin même après expiration du token.
6. **Pas de double soumission.** Un agent ne peut candidater qu'une fois par poste (contrainte unique sur `agent_id + position_id` dans `submissions`).
7. **Mail en queue.** Utiliser `ShouldQueue` sur les notifications pour ne pas bloquer l'action Filament.

---

## À ne pas faire

- Ne pas utiliser `php artisan make:auth` (Filament gère l'authentification admin).
- Ne pas exposer les fichiers via le disk `public`. Toujours `private`.
- Ne pas stocker les dates iHRIS comme entiers — les convertir en `date` à l'import.
- Ne pas envoyer de mail si `agent->email` est null — afficher le message copier-coller à la place.
- Ne pas bloquer l'action admin si l'email est absent — les deux canaux doivent fonctionner.
- Ne pas paginer les résultats de l'import dans la réponse Filament — retourner un résumé global.

---

## Données de test (seeder)

Créer un `DatabaseSeeder` qui :
1. Importe les agents depuis le fichier Excel de référence (`database/seeders/data/agents_ihris.xlsx`)
2. Crée 1 campaign de test avec 2 postes
3. Génère 5 tokens de test (dont 2 expirés, 1 révoqué) pour tester les états du portail

---

## Statuts et transitions

```
Submission.status :
  draft        → L'agent a commencé mais pas finalisé (ou token généré, pas encore visité)
  submitted    → Au moins une soumission complète (CV présent)
  shortlisted  → Présélectionné par le recruteur
  rejected     → Rejeté par le recruteur

InvitationToken :
  actif + non expiré + non révoqué → accès portail
  expiré (expires_at < now())      → page expired
  révoqué (revoked_at != null)     → page error
```

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
