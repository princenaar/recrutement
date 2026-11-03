<?php

namespace App\Services;

use Dotenv\Dotenv;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ConfigurationUrlParser;
use PDO;
use RuntimeException;
use Throwable;

class DeploymentPreflight
{
    public function __construct(
        private DatabaseManager $database,
        private Repository $config,
        private Application $application,
        private Migrator $migrator,
    ) {}

    public static function clearProviderManifests(LaravelApplication $application): void
    {
        foreach ([$application->getCachedServicesPath(), $application->getCachedPackagesPath()] as $path) {
            if (is_link($path) || (file_exists($path) && (! is_file($path) || ! @unlink($path)))) {
                throw new RuntimeException('Nettoyage des manifests providers impossible.');
            }
        }
    }

    /** @return array<string, string> */
    public static function parameters(string $directory): array
    {
        try {
            $environment = getenv('APP_ENV');
            $filename = $environment && is_file($directory.'/.env.'.$environment) ? '.env.'.$environment : '.env';
            $values = Dotenv::createArrayBacked($directory, $filename)->load();
        } catch (Throwable) {
            throw new RuntimeException('Paramètres de déploiement illisibles.');
        }
        $parameters = [];
        foreach (['DEPLOY_DB_DATABASE', 'DEPLOY_DB_SERVER_UUID', 'DEPLOY_BACKUP_REFERENCE', 'DEPLOY_WRITERS_STOPPED'] as $key) {
            $external = getenv($key);
            $value = $external !== false ? $external : ($values[$key] ?? '');
            if (! is_string($value) || trim($value) === '' || preg_match('/[\x00-\x1F\x7F]/', $value)) {
                throw new RuntimeException('Paramètre obligatoire absent ou invalide : '.$key.'.');
            }
            $parameters[$key] = $value;
        }
        if ($parameters['DEPLOY_WRITERS_STOPPED'] !== '1') {
            throw new RuntimeException('DEPLOY_WRITERS_STOPPED=1 est requis après arrêt des autres écritures.');
        }

        return $parameters;
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{fingerprint: string, connection: string, maintenance: bool, url: string, identity: array<string, mixed>, backup: string, maintenance_path: string}
     */
    public function inspect(array $parameters, bool $requireReady = false): array
    {
        if ($this->application->environment() !== 'production' || $this->config->get('app.debug')
            || ! is_string($this->config->get('app.key')) || $this->config->get('app.key') === ''
            || $this->config->get('app.maintenance.driver') !== 'file') {
            throw new RuntimeException('Le déploiement exige production, debug désactivé, une clé et la maintenance fichier.');
        }
        $name = (string) $this->config->get('database.default');
        [$connection, $identity, $resolved] = $this->target($name, $parameters);
        $cache = $this->cacheTargets($name, $parameters);
        try {
            $table = $this->config->get('database.migrations.table', 'migrations');
            if (! $connection->getSchemaBuilder()->hasTable($table)) {
                throw new RuntimeException('Dépôt de migrations absent.');
            }
            $applied = $connection->table($table)->useWritePdo()->pluck('migration')->all();
            $known = array_keys($this->migrator->getMigrationFiles([$this->application->databasePath('migrations')]));
            if ($applied === [] || array_diff($applied, $known) || ($requireReady && array_diff($known, $applied))) {
                throw new RuntimeException('Migrations manquantes ou inconnues.');
            }
        } catch (Throwable) {
            throw new RuntimeException('Schéma de migrations non vérifié ; aucune initialisation ni ouverture autorisée.');
        }
        $this->verifyAssets();
        $url = rtrim((string) $this->config->get('app.url'), '/');
        $parts = parse_url($url);
        if (! is_array($parts) || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment']) || preg_match('/[\x00-\x20\x7F]/', $url)) {
            throw new RuntimeException('APP_URL doit être une URL HTTP sans identifiants ni paramètres.');
        }
        $maintenancePath = $this->application->storagePath('framework/down');
        $report = ['connection' => $name, 'identity' => $identity, 'resolved' => $resolved,
            'cache' => $cache, 'url' => $url, 'backup' => $parameters['DEPLOY_BACKUP_REFERENCE'],
            'key_hash' => hash('sha256', $this->config->get('app.key')), 'maintenance_path' => $maintenancePath];
        if (preg_match('/[\x00-\x1F\x7F]/', $name.$maintenancePath)) {
            throw new RuntimeException('Métadonnées de déploiement invalides.');
        }

        return ['fingerprint' => hash('sha256', json_encode($report, JSON_THROW_ON_ERROR)),
            'connection' => $name, 'maintenance' => $this->application->isDownForMaintenance(),
            'url' => $url, 'identity' => $identity, 'backup' => $parameters['DEPLOY_BACKUP_REFERENCE'],
            'maintenance_path' => $maintenancePath];
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{Connection, array<string, mixed>, array<string, mixed>}
     */
    private function target(string $name, array $parameters): array
    {
        try {
            $configuration = $this->config->get('database.connections.'.$name);
            if (! is_array($configuration)) {
                throw new RuntimeException;
            }
            $configuration = (new ConfigurationUrlParser)->parseConfiguration($configuration);
            if (($configuration['driver'] ?? null) !== 'mysql' || isset($configuration['read'])
                || isset($configuration['write']) || is_array($configuration['host'] ?? null)) {
                throw new RuntimeException;
            }
            $connection = $this->database->connection($name);
            if ($connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
                throw new RuntimeException;
            }
            $row = $connection->selectOne('SELECT DATABASE() AS db, @@server_uuid AS uuid, @@hostname AS host, @@port AS port, VERSION() AS version', [], false);
            $identity = ['database' => (string) $row->db, 'server_uuid' => (string) $row->uuid,
                'host' => (string) $row->host, 'port' => (int) $row->port, 'version' => (string) $row->version];
            if ($identity['database'] !== $parameters['DEPLOY_DB_DATABASE']
                || $identity['server_uuid'] !== $parameters['DEPLOY_DB_SERVER_UUID']
                || ($configuration['database'] ?? null) !== $identity['database']
                || $connection->getDatabaseName() !== $identity['database']
                || $identity['host'] === '' || $identity['port'] < 1 || $identity['version'] === '') {
                throw new RuntimeException;
            }
            $resolved = array_intersect_key($configuration, array_flip(['driver', 'host', 'port', 'database', 'unix_socket']));
            foreach ([...array_values($identity), ...array_values($resolved)] as $value) {
                if (! is_scalar($value) || preg_match('/[\x00-\x1F\x7F]/', (string) $value)) {
                    throw new RuntimeException;
                }
            }

            return [$connection, $identity, $resolved];
        } catch (Throwable) {
            throw new RuntimeException('Datasource inaccessible, ambiguë ou différente de la cible autorisée.');
        }
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array<string, mixed>
     */
    private function cacheTargets(string $defaultConnection, array $parameters): array
    {
        $store = (string) $this->config->get('cache.default');
        $configuration = $this->config->get('cache.stores.'.$store, []);
        $driver = $configuration['driver'] ?? null;
        if (in_array($driver, ['file', 'array', 'null'], true)) {
            return ['store' => $store, 'driver' => $driver, 'path' => $configuration['path'] ?? null,
                'prefix' => $this->config->get('cache.prefix')];
        }
        if ($driver !== 'database') {
            throw new RuntimeException('Datasource du cache non prise en charge ; écriture refusée.');
        }
        $targets = [];
        foreach (['connection', 'lock_connection'] as $key) {
            $name = $configuration[$key] ?? $defaultConnection;
            [, $identity, $resolved] = $this->target($name, $parameters);
            $targets[$key] = ['name' => $name, 'identity' => $identity, 'resolved' => $resolved];
        }

        return ['store' => $store, 'driver' => $driver, 'targets' => $targets,
            'table' => $configuration['table'] ?? 'cache', 'lock_table' => $configuration['lock_table'] ?? null,
            'prefix' => $this->config->get('cache.prefix')];
    }

    private function verifyAssets(): void
    {
        try {
            $directory = $this->application->publicPath('build');
            if (! is_file($directory.'/manifest.json')) {
                throw new RuntimeException;
            }
            $manifest = json_decode(file_get_contents($directory.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($manifest) || $manifest === [] || file_exists($this->application->publicPath('hot'))) {
                throw new RuntimeException;
            }
            foreach ($manifest as $entry) {
                if (! is_array($entry) || ! isset($entry['file'])) {
                    throw new RuntimeException;
                }
                foreach ([$entry['file'], ...($entry['css'] ?? []), ...($entry['assets'] ?? [])] as $file) {
                    if (! is_string($file) || str_contains($file, '..') || str_starts_with($file, '/')
                        || str_contains($file, '\\') || ! is_file($directory.'/'.$file)) {
                        throw new RuntimeException;
                    }
                }
            }
        } catch (Throwable) {
            throw new RuntimeException('Manifeste Vite ou fichiers compilés absents ou invalides.');
        }
    }
}
