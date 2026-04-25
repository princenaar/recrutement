# PLAN.md — Plan d'implémentation Recrutement Interne MSHP

Ce fichier décrit la séquence étape par étape pour livrer le MVP. Référence croisée : spécification fonctionnelle et architecturale dans [`CLAUDE.md`](./CLAUDE.md).

## Contexte

Le dépôt est un squelette Laravel 13 quasi vierge :
- `app/Models/` ne contient que `User.php`
- `database/migrations/` : seulement les migrations de base (users, cache, jobs)
- Aucun domaine métier implémenté ; Filament et `maatwebsite/excel` non installés

**Résultat attendu du MVP :** un admin peut (1) créer une campagne et des postes, (2) importer les agents iHRIS depuis Excel, (3) envoyer des invitations (mail auto ou message manuel à copier-coller), (4) voir les soumissions et les présélectionner/rejeter. L'agent peut, depuis un lien unique valide 7 jours, remplir son dossier et uploader CV + diplômes.

**Choix techniques validés :**
- Import Excel : **`maatwebsite/excel`** (conversion native des dates Excel via PhpSpreadsheet)
- Tests : **TDD strict sur les services métier** (`AgentImportService`, `InvitationService`, `SubmissionService`) ; contrôleurs et ressources Filament couverts par feature tests sans TDD strict

---

## Phase 0 — Dépendances & configuration

**Fichiers modifiés :** `composer.json`, `config/filesystems.php`, `.env`, `.env.example`, `config/recrutement.php`, `app/Providers/Filament/AdminPanelProvider.php` (créé par l'installer).

- [ ] `composer require filament/filament:"^5.0"`
- [ ] `php artisan filament:install --panels` (panneau `admin` sur `/admin`)
- [ ] `composer require maatwebsite/excel`
- [ ] `php artisan make:filament-user` (compte admin local)
- [ ] Ajouter le disk `private` dans `config/filesystems.php` (root `storage/app/private`, visibility `private`)
- [ ] Ajouter variables `.env` : `INVITATION_TOKEN_VALIDITY_DAYS=7`, `UPLOAD_MAX_SIZE_KB=5120`, `APP_URL`
- [ ] Créer `config/recrutement.php` (valeurs tirées des env vars)

**Vérif :** `php artisan serve` répond sur `/admin` avec login Filament ; `php artisan config:show recrutement` affiche les valeurs.

---

## Phase 1 — Migrations & modèles Eloquent

**Ordre strict (contraintes FK) :**

1. `php artisan make:model Campaign -mfs`
2. `php artisan make:model Position -mfs`
3. `php artisan make:model Agent -mfs`
4. `php artisan make:model InvitationToken -mfs`
5. `php artisan make:model Submission -mfs`
6. `php artisan make:model Diploma -mfs`

**Colonnes :** cf. `CLAUDE.md` section « Modèles Eloquent » — respecter exactement les noms (`matricule`, `ihris_imported_at`, `expires_at`, `revoked_at`, `submitted_at`, `status`, `cv_path`, etc.).

**Contraintes à ne pas oublier :**
- `submissions` : UNIQUE (`agent_id`, `position_id`)
- `invitation_tokens.token` : UNIQUE + index
- `agents.matricule` : UNIQUE + index
- Casts : `expires_at` / `submitted_at` / `ihris_imported_at` → `datetime` ; `birth_date` → `date`
- Enums PHP 8.1 pour `Campaign::status`, `Position::status`, `Submission::status`, canal d'invitation

**Relations à coder dans les modèles :** cf. arbre dans `CLAUDE.md`.

**Vérif :** `php artisan migrate:fresh` passe ; `php artisan tinker --execute 'App\Models\Agent::factory()->create();'` crée un agent.

---

## Phase 2 — Service d'import Excel iHRIS (TDD)

**Fichier clé :** `app/Services/AgentImportService.php`

**TDD — écrire d'abord `tests/Feature/AgentImportServiceTest.php` :**
- [ ] Import d'un fichier fixture (`tests/fixtures/agents_sample.xlsx`) → crée N agents
- [ ] Ré-import du même fichier → `updateOrCreate` sur matricule, 0 créé, N mis à jour
- [ ] Date Excel numérique (ex : `26484`) correctement convertie en `1972-07-16`
- [ ] Colonne email vide → agent créé avec `email = null` (pas d'exception)
- [ ] Retourne `ImportResult` (DTO) avec `created`, `updated`, `skipped`

**Puis implémenter le service** en utilisant `\Maatwebsite\Excel\Concerns\ToModel` ou `ToCollection` + `\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject()`.

**Vérif :** `php artisan test --compact --filter=AgentImportServiceTest` vert.

---

## Phase 3 — Service d'invitations (TDD)

**Fichiers clés :** `app/Services/InvitationService.php` + `app/Notifications/InvitationNotification.php` (implements `ShouldQueue`).

**TDD — écrire d'abord `tests/Feature/InvitationServiceTest.php` :**
- [ ] `createToken(Agent, Position)` retourne un `InvitationToken` avec `token` UUID v4, `expires_at = now()+7j`
- [ ] Token actif existant pour le couple agent/poste → régénération révoque l'ancien (`revoked_at = now()`) puis crée le nouveau (`createOrReplace`)
- [ ] `sendInvitation(InvitationToken)` avec `agent.email` présent → `Notification::fake()` vérifie l'envoi, `notification_channel = 'email'`, `notification_sent_at` set
- [ ] Si `agent.email` null → aucune notification, `notification_channel = 'manual'`, retour du texte formaté (prénom/nom/poste/URL/expiration)

**Puis implémenter** le service + la Notification.

**Vérif :** tests verts ; `php artisan queue:work` traite la queue en dev.

---

## Phase 4 — Service de soumission + gestion fichiers (TDD)

**Fichier clé :** `app/Services/SubmissionService.php`

**TDD — écrire d'abord `tests/Feature/SubmissionServiceTest.php` :**
- [ ] `saveDraft(InvitationToken, array $data, ?UploadedFile $cv)` → crée/met à jour `Submission`, stocke CV dans `submissions/{token}/cv.pdf` (disk `private`)
- [ ] Remplacement CV : l'ancien fichier est supprimé avant stockage du nouveau
- [ ] `addDiploma(Submission, array $data, UploadedFile)` → crée `Diploma`, stocke dans `submissions/{token}/diplomas/{uuid}.pdf`
- [ ] `removeDiploma(Diploma)` → supprime fichier + enregistrement
- [ ] Première soumission complète → `submitted_at = now()` ; suivantes → `last_updated_at` seulement
- [ ] Validation mimes=pdf, max 5 Mo → exception claire si KO

**Puis implémenter** avec `Storage::disk('private')`.

**Vérif :** tests verts, inspection manuelle de `storage/app/private/submissions/`.

---

## Phase 5 — Portail candidat (routes publiques)

**Fichiers :**
- `routes/web.php` : 4 routes du groupe `candidate.*` (cf. `CLAUDE.md`)
- `app/Http/Controllers/CandidatePortalController.php`
- `resources/views/candidate/portal.blade.php`
- `resources/views/candidate/expired.blade.php`
- `resources/views/candidate/submitted.blade.php`
- `resources/views/candidate/error.blade.php` (token révoqué / inexistant)

**Logique du contrôleur `show($token)` :**
- Token inexistant → `abort(404)`
- `revoked_at` non null → vue `candidate.error`
- `expires_at < now()` → vue `candidate.expired`
- Valide → vue `candidate.portal` avec agent + submission existante (si déjà entamée)

**Formulaire Blade :**
- **Section A** (readonly, depuis `$agent`) : matricule, prénom, nom, genre, date naissance, catégorie, statut agent, type contrat, employeur, entry_date, structure iHRIS, district, région
- **Section B** (éditable) : current_structure (pré-rempli), current_service (pré-rempli), years_in_service (auto si `position_start_date`, sinon input), motivation_note (textarea optionnel)
- **Section C** : input file CV (PDF 5 Mo) + liste diplômes existants avec suppression + formulaire ajout diplôme (titre/institution/année/fichier)

**Feature test `tests/Feature/CandidatePortalTest.php` :**
- Token valide → 200 + HTML contient matricule
- Token expiré → vue `expired` (200)
- Token révoqué → vue `error`
- Token invalide → 404
- POST met à jour la submission et stocke le CV
- Tentative de modification des champs iHRIS via POST → ignorée (mass-assignment protégé via `$fillable` de `Submission`)

**Style :** Tailwind v4, pas de JS custom — Blade + `@csrf`.

---

## Phase 6 — Back-office Filament : ressources CRUD

Créer dans cet ordre :

1. **`CampaignResource`** (`php artisan make:filament-resource Campaign --generate`)
   - Table : titre, status (badge), nombre postes, nombre candidatures, dates
   - Form : title, description, status, starts_at/ends_at
   - `PositionsRelationManager` inline

2. **`AgentResource`** — **readonly** (désactiver `canCreate` / `canEdit` / `canDelete`)
   - Table filtrable : Matricule · Prénom · Nom · Structure · Catégorie · Email · Statut invitation (calculé)
   - Filtres : structure, région, "invité" / "non invité" / "soumis"
   - **HeaderAction** `ImportAgents` → modal upload Excel → `AgentImportService::import()` → toast résumé
   - **RowAction** `InviteForPosition` → modal campagne→poste → `InvitationService::createOrReplace()` + `sendInvitation()`

3. **`ApplicationResource`** (vue principale recruteur, sur `Submission`)
   - Table : Candidat · Poste · Campagne · Date soumission · Statut (badge) · Actions
   - Filtres : campagne, poste, statut
   - ViewPage détaillée : infos agent + submission + liens de téléchargement CV/diplômes (via route admin protégée, pas de lien direct `storage/`)
   - Actions : `ShortlistAction`, `RejectAction` (modal `rejection_note`), `RegenerateTokenAction`, `DownloadCv`, `DownloadDiploma`

4. **`InvitationTokenResource`** (vue de suivi)
   - Table : Agent · Poste · Expiré le · Canal · Statut
   - Actions : `Revoke`, `CopyLink`, `ResendEmail`

**Route admin pour téléchargement fichiers privés :**
```
GET /admin/files/submission/{submission}/cv → StreamedResponse via Storage::disk('private')
GET /admin/files/diploma/{diploma}          → idem
```
Protégées par le middleware du panel Filament.

---

## Phase 7 — Actions Filament dédiées

**Fichiers :**
- `app/Filament/Actions/SendInvitationAction.php`
- `app/Filament/Actions/ShortlistAction.php`
- `app/Filament/Actions/RejectAction.php`
- `app/Filament/Actions/ImportAgentsAction.php`

Chaque action délègue au service métier (pas de logique dans Filament).

- **`ShortlistAction`** : `submission->update(['status' => 'shortlisted', 'shortlisted_at' => now(), 'shortlisted_by' => auth()->id()])`
- **`RejectAction`** : modal `rejection_note` (textarea), puis `status = 'rejected'`
- **`SendInvitationAction`** : si canal manuel → modal affichant le texte copier-coller avec bouton « Copier »

---

## Phase 8 — Seeders & données de test

**Fichier :** `database/seeders/DatabaseSeeder.php` + fixture `database/seeders/data/agents_ihris_sample.xlsx` (5-10 lignes fictives).

`DatabaseSeeder::run()` :
1. Crée un user admin Filament
2. Importe le fichier sample via `AgentImportService`
3. Crée 1 `Campaign` active avec 2 `Position`
4. Génère 5 `InvitationToken` couvrant tous les états : 2 actifs, 2 expirés (`expires_at = now()->subDay()`), 1 révoqué

**Vérif :** `php artisan migrate:fresh --seed` puis navigation manuelle des 5 tokens pour valider visuellement les 3 vues candidat.

---

## Phase 9 — Feature tests end-to-end

**Fichier :** `tests/Feature/RecrutementFlowTest.php`

- [ ] Admin importe Excel → 5 agents créés
- [ ] Admin crée campagne + poste
- [ ] Admin invite agent avec email → notification envoyée (`Notification::fake()`)
- [ ] Admin invite agent sans email → texte manuel retourné
- [ ] Agent visite l'URL token → voit formulaire pré-rempli
- [ ] Agent POST formulaire + CV → `submitted_at` set, fichier présent (`Storage::fake('private')`)
- [ ] Agent re-POST → `last_updated_at` mis à jour, `submitted_at` inchangé
- [ ] Admin présélectionne → `status = shortlisted`
- [ ] Admin rejette avec note → `status = rejected`, note stockée

**Lancer :** `php artisan test --compact` — tout vert.

---

## Phase 10 — Finitions & livraison

- [ ] `vendor/bin/pint --dirty --format agent` sur tout le PHP touché
- [ ] Revue manuelle des 3 vues candidat + back-office avec le seeder
- [ ] Vérifier `php artisan queue:work` traite bien les notifications email
- [ ] Mettre à jour `CLAUDE.md` si des décisions d'implémentation divergent de la spec (en l'annotant)
- [ ] Valider le flow complet avec un compte Filament admin de test

---

## Fichiers critiques à modifier / créer

| Phase | Chemin | Action |
|-------|--------|--------|
| 0 | `composer.json`, `config/filesystems.php`, `.env.example`, `config/recrutement.php` | install + config |
| 1 | `database/migrations/*`, `app/Models/{Campaign,Position,Agent,InvitationToken,Submission,Diploma}.php` | créer |
| 2 | `app/Services/AgentImportService.php`, `tests/Feature/AgentImportServiceTest.php` | TDD |
| 3 | `app/Services/InvitationService.php`, `app/Notifications/InvitationNotification.php`, tests | TDD |
| 4 | `app/Services/SubmissionService.php`, tests | TDD |
| 5 | `routes/web.php`, `app/Http/Controllers/CandidatePortalController.php`, 4 vues Blade | créer |
| 6 | `app/Filament/Resources/{Campaign,Agent,Application,InvitationToken}Resource.php` + Pages | créer |
| 7 | `app/Filament/Actions/*.php` | créer |
| 8 | `database/seeders/DatabaseSeeder.php`, `database/seeders/data/agents_ihris_sample.xlsx` | créer |
| 9 | `tests/Feature/RecrutementFlowTest.php` | créer |

---

## Vérification finale — smoke test end-to-end

```bash
# Depuis zéro
php artisan migrate:fresh --seed
composer dev                     # lance serve + queue:listen + vite
# Puis dans un autre terminal :
php artisan test --compact       # tous verts
```

**À la main :**
1. Login `/admin` avec le compte seed
2. `AgentResource` → voir 5+ agents importés
3. Créer une nouvelle campagne + poste
4. Inviter 2 agents (1 avec email, 1 sans) → vérifier `log` de mail + modal manuel
5. Ouvrir les 5 tokens du seeder dans 5 onglets incognito → valider les 3 états (valide / expiré / révoqué)
6. Sur un token valide : remplir le formulaire, uploader un PDF (CV), ajouter un diplôme → retour admin → voir la submission dans `ApplicationResource` → télécharger le CV
7. Présélectionner puis rejeter deux submissions différentes → valider les badges de statut

Si les 7 étapes passent, le MVP est livrable.
