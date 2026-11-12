#!/usr/bin/env bash

# Mises à jour cPanel : bash deploy.sh (depuis une installation déjà configurée).
# PHP >= 8.4, Composer, Git, cURL et mariadb-dump ou mysqldump sont requis.
# Définir la cible DEPLOY_* et confirmer l’arrêt des workers/crons et autres écritures.
# La sauvegarde privée est créée automatiquement ; la reprise des écritures reste manuelle.
# Installer ce script et ses services Deployment* depuis la même révision.

set -Eeuo pipefail
readonly EXPECTED_BRANCH="main"
readonly APPLICATION_DIRECTORY="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly DEPLOY_LOCK_DIRECTORY="${TMPDIR:-/tmp}/recrutement-${USER:-cpanel}-deploy.lock"
phase="prerequis"
lock_acquired=0
maintenance_requested=0
opening_attempted=0
maintenance_on_entry=0
maintenance_path=""
baseline_fingerprint=""
revision_before="inconnue"
revision_target="inconnue"
revision_after="inconnue"
journal=""
backup_completed=0
backup_credentials=""

log() {
    local message
    message="[$(date '+%Y-%m-%dT%H:%M:%S%z')] phase=$phase $*"
    printf '%s\n' "$message"
    if [[ -n "$journal" ]]; then
        printf '%s\n' "$message" >> "$journal"
    fi
}

ensure_maintenance() {
    if [[ "$opening_attempted" -eq 1 ]]; then
        log "Réactivation de la maintenance ; reprise opérateur obligatoire."
        php artisan down --no-interaction || log "Échec artisan down ; activation du marqueur fichier."
    fi
    if [[ ! -f "$maintenance_path" ]]; then
        printf '%s\n' '{"except":[],"redirect":null,"retry":null,"refresh":null,"secret":null,"status":503,"template":null}' \
            > "$maintenance_path.deploy-$$" && mv -f -- "$maintenance_path.deploy-$$" "$maintenance_path"
    fi
    if [[ -f "$maintenance_path" ]]; then
        log "Maintenance fichier vérifiée ; aucune restauration automatique."
    else
        log "CRITIQUE : maintenance impossible à confirmer ; intervention opérateur immédiate."
    fi
}

on_failure() {
    local exit_code="$1"
    [[ "$BASHPID" == "$$" ]] || exit "$exit_code"
    trap - ERR INT TERM
    set +e
    if [[ "$maintenance_requested" -eq 1 || "$opening_attempted" -eq 1 ]]; then
        ensure_maintenance
    fi
    log "Échec code=$exit_code ; reprise opérateur requise."
    exit "$exit_code"
}

fail() {
    log "ERREUR : $*" >&2
    on_failure 1
}

cleanup() {
    local exit_code=$?
    [[ "$BASHPID" == "$$" ]] || exit "$exit_code"
    trap - EXIT ERR INT TERM
    set +e
    if [[ -n "$backup_credentials" && -f "$backup_credentials" && ! -L "$backup_credentials" ]]; then
        rm -f -- "$backup_credentials" || log "Suppression du fichier privé d’authentification impossible ; intervention requise."
    fi
    log "Résultat code=$exit_code avant=$revision_before cible=$revision_target après=$revision_after"
    if [[ "$lock_acquired" -eq 1 && -f "$DEPLOY_LOCK_DIRECTORY/pid" \
        && "$(<"$DEPLOY_LOCK_DIRECTORY/pid")" == "$$" ]]; then
        rm -f -- "$DEPLOY_LOCK_DIRECTORY/pid"
        rmdir -- "$DEPLOY_LOCK_DIRECTORY" 2>/dev/null
    fi
    exit "$exit_code"
}

preflight() {
    DEPLOY_REQUIRE_READY="$1" php <<'PHP'
<?php
try {
    require __DIR__.'/vendor/autoload.php';
    $parameters = App\Services\DeploymentPreflight::parameters(__DIR__);
    $app = require __DIR__.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $report = $app->make(App\Services\DeploymentPreflight::class)->inspect($parameters, getenv('DEPLOY_REQUIRE_READY') === '1');
    echo implode("\t", [$report['fingerprint'], $report['connection'], (int) $report['maintenance'],
        $report['url'], json_encode($report['identity'], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        $report['backup'], $report['maintenance_path']]);
} catch (Throwable) {
    fwrite(STDERR, "Précontrôle refusé : vérifier environnement, paramètres, datasource, cache et migrations. Aucun détail sensible affiché.\n");
    exit(1);
}
PHP
}

clear_provider_manifests() {
    DEPLOY_CLEAR_MANIFESTS=1 php <<'PHP'
<?php
try {
    require __DIR__.'/vendor/autoload.php';
    $app = new Illuminate\Foundation\Application(__DIR__);
    $app->bootstrapWith([Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class]);
    App\Services\DeploymentPreflight::clearProviderManifests($app);
} catch (Throwable) {
    fwrite(STDERR, "Nettoyage des manifests providers refusé ; maintenance conservée.\n");
    exit(1);
}
PHP
}

prepare_backup() {
    DEPLOY_BACKUP_PREPARE=1 php <<'PHP'
<?php
try {
    require __DIR__.'/vendor/autoload.php';
    echo App\Services\DeploymentBackup::prepare(__DIR__, getenv('HOME') ?: '');
} catch (Throwable) {
    fwrite(STDERR, "Préparation du dossier privé de sauvegarde impossible.\n");
    exit(1);
}
PHP
}

create_backup() {
    DEPLOY_BACKUP_CREATE=1 DEPLOY_FINGERPRINT="$baseline_fingerprint" DEPLOY_REVISION="$revision_before" php <<'PHP'
<?php
try {
    require __DIR__.'/vendor/autoload.php';
    $parameters = App\Services\DeploymentPreflight::parameters(__DIR__);
    $app = require __DIR__.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $result = $app->make(App\Services\DeploymentBackup::class)->create($parameters, getenv('DEPLOY_FINGERPRINT'), getenv('DEPLOY_REVISION'));
    echo 'Sauvegarde privée vérifiée '.json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable) {
    fwrite(STDERR, "Sauvegarde refusée ou incomplète ; maintenance conservée. Aucun détail sensible affiché.\n");
    exit(1);
}
PHP
}

verify_target() {
    local report fingerprint connection active url identity backup path fresh_report fresh_fingerprint
    if ! report="$(preflight "$1")"; then
        fail "la datasource effective ou les préconditions ne sont pas vérifiées."
    fi
    IFS=$'\t' read -r fingerprint connection active url identity backup path <<< "$report"
    [[ "$fingerprint" =~ ^[a-f0-9]{64}$ && "$active" =~ ^[01]$ && -n "$path" ]] \
        || fail "rapport de précontrôle invalide."
    if [[ -z "$baseline_fingerprint" ]]; then
        baseline_fingerprint="$fingerprint"
        maintenance_on_entry="$active"
        maintenance_path="$path"
    fi
    [[ "$fingerprint" == "$baseline_fingerprint" ]] || fail "la configuration ou la cible a changé."
    [[ ! -e "$DEPLOY_LOCK_DIRECTORY/uncached.php" && ! -L "$DEPLOY_LOCK_DIRECTORY/uncached.php" ]] \
        || fail "le chemin de contrôle sans cache doit être absent."
    if ! fresh_report="$(APP_CONFIG_CACHE="$DEPLOY_LOCK_DIRECTORY/uncached.php" preflight "$1")"; then
        fail "la configuration sans cache n'est pas vérifiée."
    fi
    fresh_fingerprint="${fresh_report%%$'\t'*}"
    [[ "$fresh_fingerprint" == "$baseline_fingerprint" ]] \
        || fail "les configurations effective et sans cache diffèrent."
    database_connection="$connection"
    application_url="$url"
    target_identity="$identity"
    log "Cible vérifiée connexion=$connection identité=$identity sauvegarde=$backup maintenance=$active"
    if [[ "$maintenance_requested" -eq 1 ]]; then
        [[ "$active" == "1" && -f "$maintenance_path" ]] || fail "la maintenance n'est plus active."
    fi
}

migration_preview() {
    DEPLOY_PREVIEW=1 run_migrations
}

run_migrations() {
    [[ "$backup_completed" -eq 1 ]] || fail "une sauvegarde vérifiée est requise avant toute migration."
    DEPLOY_FINGERPRINT="$baseline_fingerprint" DEPLOY_REVISION="$revision_after" php -r '
try {
    require getcwd()."/vendor/autoload.php";
    $parameters = App\Services\DeploymentPreflight::parameters(getcwd());
    $app = require getcwd()."/bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    exit($app->make(App\Services\DeploymentMigrationGuard::class)->run(
        $parameters, getenv("DEPLOY_FINGERPRINT"), getenv("DEPLOY_REVISION"), getenv("DEPLOY_PREVIEW") === "1"
    ));
} catch (Throwable) {
    fwrite(STDERR, "Migration protégée refusée ; maintenance conservée.\n");
    exit(1);
}'
}

main() {
    local command_name current_branch origin_url http_status
    [[ "$#" -eq 0 ]] || fail "utilisation : bash deploy.sh (aucune option acceptée)."
    cd "$APPLICATION_DIRECTORY"
    for command_name in git php composer curl; do
        command -v "$command_name" >/dev/null 2>&1 || fail "commande '$command_name' introuvable."
    done
    command -v mariadb-dump >/dev/null 2>&1 || command -v mysqldump >/dev/null 2>&1 \
        || fail "mariadb-dump ou mysqldump est requis pour la sauvegarde automatique."
    php -r 'exit(PHP_VERSION_ID >= 80400 ? 0 : 1);' \
        || fail "PHP 8.4 minimum requis."
    [[ -f .env && -f artisan && -f vendor/autoload.php && -f public/build/manifest.json ]] \
        || fail ".env, artisan, dépendances installées et manifeste Vite sont requis."
    [[ -f app/Services/DeploymentPreflight.php ]] || fail "installer le script et son service depuis la même révision."
    [[ -f app/Services/DeploymentMigrationGuard.php ]] || fail "le garde des requêtes de migration est requis."
    [[ -f app/Services/DeploymentBackup.php ]] || fail "le service de sauvegarde automatique est requis."
    for command_name in storage/framework storage/framework/cache storage/framework/views storage/logs bootstrap/cache public; do
        [[ -d "$command_name" && -w "$command_name" ]] || fail "répertoire '$command_name' absent ou non inscriptible."
    done
    [[ ! -e public/hot && ! -L public/hot ]] || fail "public/hot doit être absent en production."
    git rev-parse --is-inside-work-tree >/dev/null
    if ! (umask 077; mkdir "$DEPLOY_LOCK_DIRECTORY") 2>/dev/null; then
        fail "un déploiement est déjà verrouillé ; aucune suppression automatique."
    fi
    lock_acquired=1
    printf '%s\n' "$$" > "$DEPLOY_LOCK_DIRECTORY/pid"
    mkdir -p storage/logs
    [[ ! -L storage/logs/deployments.log ]] || fail "le journal ne doit pas être un lien symbolique."
    journal="storage/logs/deployments.log"
    (umask 077; touch "$journal")
    chmod 600 "$journal"
    current_branch="$(git branch --show-current)"
    [[ "$current_branch" == "$EXPECTED_BRANCH" ]] || fail "branche main requise."
    origin_url="$(git remote get-url origin)"
    case "$origin_url" in
        git@github.com:princenaar/recrutement.git|https://github.com/princenaar/recrutement.git) ;;
        *) fail "remote origin inattendu." ;;
    esac
    [[ -z "$(git status --porcelain)" ]] || fail "le dépôt contient des changements locaux."
    revision_before="$(git rev-parse HEAD)"
    revision_after="$revision_before"
    phase="preparation-sauvegarde"
    if ! DEPLOY_BACKUP_REFERENCE="$(prepare_backup)"; then
        fail "le dossier privé de sauvegarde n'est pas prêt."
    fi
    export DEPLOY_BACKUP_REFERENCE
    backup_credentials="$(dirname "$DEPLOY_BACKUP_REFERENCE")/.client.cnf"
    phase="datasource-initiale"
    verify_target 0
    phase="git-fetch"
    git fetch --prune origin "$EXPECTED_BRANCH"
    revision_target="$(git rev-parse "origin/$EXPECTED_BRANCH")"
    git merge-base --is-ancestor HEAD "origin/$EXPECTED_BRANCH"
    phase="maintenance"
    maintenance_requested=1
    if [[ "$maintenance_on_entry" -eq 0 ]]; then
        php artisan down --no-interaction
    fi
    verify_target 0
    phase="sauvegarde"
    create_backup
    [[ -s "$DEPLOY_BACKUP_REFERENCE" ]] || fail "le fichier de sauvegarde est absent ou vide."
    verify_target 0
    backup_completed=1
    phase="git-fast-forward"
    git merge --ff-only "$revision_target"
    revision_after="$(git rev-parse HEAD)"
    [[ "$revision_after" == "$revision_target" ]] || fail "révision cible non appliquée."
    verify_target 0
    phase="composer-install"
    composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader --no-scripts --no-plugins
    composer check-platform-reqs --no-dev --no-interaction --no-plugins
    phase="composer-manifests"
    clear_provider_manifests
    verify_target 0
    phase="configuration-clear"
    php artisan config:clear --no-interaction
    verify_target 0
    phase="composer-hooks"
    composer run-script post-autoload-dump --no-dev --no-interaction --no-plugins
    verify_target 0
    phase="actifs"
    [[ -f public/build/manifest.json ]] || fail "le commit déployé ne contient pas le manifeste Vite."
    verify_target 0
    phase="statut-migrations"
    php artisan migrate:status --database="$database_connection" --pending --no-interaction
    phase="apercu-migrations"
    log "Aperçu indicatif ; les autorisations porteront sur les requêtes réelles avant exécution."
    migration_preview
    phase="migrations"
    verify_target 0
    DEPLOY_PREVIEW=0 run_migrations
    for command_name in config:cache event:cache route:cache view:cache; do
        phase="$command_name"
        php artisan "$command_name" --no-interaction
        verify_target 0
    done
    phase="controles-internes"
    verify_target 1
    phase="workers"
    php artisan queue:restart --no-interaction
    verify_target 1
    [[ -f public/build/manifest.json ]] || fail "manifeste Vite absent."
    if [[ "$maintenance_on_entry" -eq 1 ]]; then
        phase="termine-en-maintenance"
        log "Prêt, maintenance conservée ; ouverture manuelle par l'opérateur."
        return
    fi
    phase="ouverture"
    opening_attempted=1
    php artisan up --no-interaction
    [[ ! -e "$maintenance_path" ]] || fail "la maintenance n'a pas été désactivée."
    phase="sante-http"
    http_status="$(curl --fail --silent --show-error --max-time 15 --output /dev/null --write-out '%{http_code}' "$application_url/up")"
    [[ "$http_status" == "200" ]] || fail "le contrôle HTTP ne renvoie pas 200."
    phase="termine"
    log "Déploiement terminé et santé HTTP vérifiée."
    opening_attempted=0
    maintenance_requested=0
}

trap cleanup EXIT
trap 'on_failure "$?"' ERR
trap 'on_failure 130' INT
trap 'on_failure 143' TERM
main "$@"
