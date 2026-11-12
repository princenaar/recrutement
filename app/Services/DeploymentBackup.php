<?php

namespace App\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ConfigurationUrlParser;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class DeploymentBackup
{
    public function __construct(
        private DeploymentPreflight $preflight,
        private DatabaseManager $database,
        private Application $application,
    ) {}

    public static function prepare(string $applicationDirectory, string $homeDirectory): string
    {
        $application = realpath($applicationDirectory);
        $home = realpath($homeDirectory);
        if ($application === false || $home === false || self::inside($home, $application)) {
            throw new RuntimeException('Emplacement privé de sauvegarde introuvable.');
        }
        $root = $home.'/recrutement-backups';
        if (self::inside($root, self::publicDirectory($application))) {
            throw new RuntimeException('Sauvegarde dans un emplacement public refusée.');
        }
        if (is_link($root) || (! is_dir($root) && ! mkdir($root, 0700))) {
            throw new RuntimeException('Répertoire privé de sauvegarde indisponible.');
        }
        if (self::inside((string) realpath($root), $application)
            || self::inside((string) realpath($root), $home.'/public_html')
            || ! chmod($root, 0700)) {
            throw new RuntimeException('Sauvegarde dans un emplacement public refusée.');
        }
        $directory = $root.'/'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(8));
        if (! mkdir($directory, 0700)) {
            throw new RuntimeException('Création du dossier de sauvegarde impossible.');
        }

        return $directory.'/database.sql';
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{path: string, sha256: string, tables: int, bytes: int}
     */
    public function create(array $parameters, string $fingerprint, string $revision): array
    {
        $credentials = null;
        try {
            $path = $parameters['DEPLOY_BACKUP_REFERENCE'];
            $directory = dirname($path);
            $resolved = realpath($directory);
            if ($resolved === false || is_link($directory) || basename($path) !== 'database.sql'
                || self::inside($resolved, $this->application->basePath())
                || self::inside($resolved, self::publicDirectory($this->application->basePath()))
                || (PHP_OS_FAMILY !== 'Windows' && (fileperms($directory) & 0077) !== 0)) {
                throw new RuntimeException;
            }
            foreach ([$path, $path.'.partial', $directory.'/manifest.json', $directory.'/.client.cnf'] as $file) {
                if (file_exists($file) || is_link($file)) {
                    throw new RuntimeException;
                }
            }
            $report = $this->verifiedTarget($parameters, $fingerprint);
            $connection = $this->database->connection($report['connection']);
            $config = (new ConfigurationUrlParser)->parseConfiguration($connection->getConfig());
            if (! in_array($config['driver'] ?? null, ['mysql', 'mariadb'], true)
                || ($config['database'] ?? null) !== $report['identity']['database']
                || isset($config['read']) || isset($config['write'])
                || is_array($config['host'] ?? null) || ! empty($config['options'])) {
                throw new RuntimeException;
            }
            $tables = $connection->select(
                'SELECT TABLE_NAME AS name, ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ? ORDER BY TABLE_NAME',
                [$report['identity']['database'], 'BASE TABLE'], false,
            );
            if ($tables === []) {
                throw new RuntimeException;
            }
            $names = [];
            foreach ($tables as $table) {
                if (strtolower((string) $table->engine) !== 'innodb') {
                    throw new RuntimeException;
                }
                $names[] = (string) $table->name;
            }
            $client = $this->dumpBinary(isset($report['identity']['server_uid']));
            $credentialPath = $directory.'/.client.cnf';
            self::writePrivate($credentialPath, '[client]'."\n".'user='.self::optionValue($config['username'])."\n".'password='.self::optionValue($config['password'] ?? '')."\n");
            $credentials = $credentialPath;
            self::writePrivate($path.'.partial', '');
            $socket = $config['unix_socket'] ?? '';
            if ($socket === '' && ($config['host'] ?? '') === 'localhost') {
                $socket = (string) $connection->selectOne('SELECT @@socket AS socket', [], false)->socket;
                if ($socket === '') {
                    throw new RuntimeException;
                }
            }
            $transport = $socket !== ''
                ? ['--protocol=SOCKET', '--socket='.$socket]
                : ['--protocol=TCP', '--host='.$config['host'], '--port='.($config['port'] ?? 3306)];
            $arguments = [$client, '--defaults-file='.$credentials, ...$transport,
                '--single-transaction', '--quick', '--skip-lock-tables', '--hex-blob',
                '--routines', '--events', '--triggers', '--comments', '--dump-date',
                '--default-character-set=utf8mb4', '--result-file='.$path.'.partial',
                '--databases', '--', $report['identity']['database']];
            $this->verifiedTarget($parameters, $fingerprint);
            $process = $this->dumpProcess($arguments);
            if ($process->run() !== 0) {
                throw new RuntimeException;
            }
            $this->verifiedTarget($parameters, $fingerprint);
            $this->verifyDump($path.'.partial', $names);
            $digest = hash_file('sha256', $path.'.partial');
            $bytes = filesize($path.'.partial');
            if ($digest === false || $bytes === false || $bytes === 0 || ! preg_match('/\A[a-f0-9]{40}\z/', $revision)) {
                throw new RuntimeException;
            }
            self::writePrivate($directory.'/manifest.json', json_encode([
                'created_at' => gmdate('c'), 'revision' => $revision,
                'identity' => $report['identity'], 'tables' => $names,
                'bytes' => $bytes, 'sha256' => $digest,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
            if (! rename($path.'.partial', $path) || hash_file('sha256', $path) !== $digest) {
                throw new RuntimeException;
            }

            return ['path' => $path, 'sha256' => $digest, 'tables' => count($names), 'bytes' => $bytes];
        } catch (Throwable) {
            throw new RuntimeException('Sauvegarde refusée ou incomplète ; vérifier la cible, les tables InnoDB, les droits et le client mariadb-dump/mysqldump.');
        } finally {
            if ($credentials !== null && is_file($credentials) && ! unlink($credentials)) {
                throw new RuntimeException('Suppression du fichier privé d’authentification impossible ; déploiement interrompu.');
            }
        }
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array<string, mixed>
     */
    private function verifiedTarget(array $parameters, string $fingerprint): array
    {
        $report = $this->preflight->inspect($parameters);
        if (! hash_equals($fingerprint, $report['fingerprint']) || ! $report['maintenance']) {
            throw new RuntimeException;
        }

        return $report;
    }

    protected function dumpBinary(bool $mariaDb): string
    {
        $finder = new ExecutableFinder;
        foreach ($mariaDb ? ['mariadb-dump', 'mysqldump'] : ['mysqldump', 'mariadb-dump'] as $name) {
            if (($path = $finder->find($name)) !== null) {
                return $path;
            }
        }

        throw new RuntimeException;
    }

    /** @param list<string> $arguments */
    protected function dumpProcess(array $arguments): Process
    {
        $loginFile = substr($arguments[1], strlen('--defaults-file=')).'.disabled';
        if (file_exists($loginFile) || is_link($loginFile)) {
            throw new RuntimeException;
        }

        return new Process($arguments, $this->application->basePath(), ['MYSQL_PWD' => false, 'MYSQL_TEST_LOGIN_FILE' => $loginFile], null, null);
    }

    /** @param list<string> $names */
    private function verifyDump(string $path, array $names): void
    {
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException;
        }
        $found = [];
        $last = '';
        try {
            while (($line = fgets($stream)) !== false) {
                if (preg_match('/^CREATE TABLE \x60((?:\x60\x60|[^\x60])+)\x60\s*\(/', $line, $matches)) {
                    $found[] = str_replace(chr(96).chr(96), chr(96), $matches[1]);
                }
                if (trim($line) !== '') {
                    $last = $line;
                }
            }
            if (! feof($stream)) {
                throw new RuntimeException;
            }
        } finally {
            fclose($stream);
        }
        sort($found);
        sort($names);
        if ($found !== $names || ! str_starts_with($last, '-- Dump completed on ')) {
            throw new RuntimeException;
        }
    }

    private static function publicDirectory(string $application): string
    {
        $directory = str_replace('\\', '/', $application);
        while (true) {
            if (in_array(strtolower(basename($directory)), ['public_html', 'www', 'htdocs'], true)) {
                return $directory;
            }
            $parent = str_replace('\\', '/', dirname($directory));
            if ($parent === $directory) {
                break;
            }
            $directory = $parent;
        }

        return $application;
    }

    private static function inside(string $path, string $parent): bool
    {
        $path = str_replace('\\', '/', $path);
        $parent = rtrim(str_replace('\\', '/', $parent), '/');
        if (PHP_OS_FAMILY === 'Windows') {
            $path = strtolower($path);
            $parent = strtolower($parent);
        }

        return $path === $parent || str_starts_with($path, $parent.'/');
    }

    private static function optionValue(string $value): string
    {
        if (str_contains($value, "\0")) {
            throw new RuntimeException;
        }

        return '"'.strtr($value, ['\\' => '\\\\', '"' => '\\"', "\n" => '\\n', "\r" => '\\r', "\t" => '\\t']).'"';
    }

    private static function writePrivate(string $path, string $content): void
    {
        $stream = fopen($path, 'x+b');
        if ($stream === false) {
            throw new RuntimeException;
        }
        $completed = false;
        try {
            if (! chmod($path, 0600) || fwrite($stream, $content) !== strlen($content) || ! fflush($stream)) {
                throw new RuntimeException;
            }
            $completed = true;
        } finally {
            fclose($stream);
            if (! $completed) {
                unlink($path);
            }
        }
    }
}
