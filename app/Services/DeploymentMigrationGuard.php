<?php

namespace App\Services;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\ConnectionEstablished;
use RuntimeException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;

class DeploymentMigrationGuard
{
    public function __construct(
        private DeploymentPreflight $preflight,
        private DatabaseManager $database,
        private Dispatcher $events,
        private Application $application,
        private Kernel $kernel,
    ) {}

    /** @param array<string, string> $parameters */
    public function run(array $parameters, string $fingerprint, string $revision, bool $preview = false): int
    {
        $initial = $this->assertState($parameters, $fingerprint, $revision);
        $checking = false;
        $guard = function (string $query, array $bindings, Connection $connection) use ($parameters, $fingerprint, $revision, $initial, $preview, &$checking): void {
            if ($checking) {
                return;
            }
            if ($connection !== $this->database->connection($initial['connection'])) {
                throw new RuntimeException('Migration sur une connexion secondaire refusée.');
            }
            if ($connection->pretending() || self::isReadOnly($query)) {
                return;
            }
            self::assertWriteScope($query, $initial['identity']['database']);
            if ($preview) {
                throw new RuntimeException('Écriture réelle refusée pendant l’aperçu des migrations.');
            }
            $checking = true;
            try {
                $this->assertState($parameters, $fingerprint, $revision);
                if (self::requiresApproval($query)) {
                    $sql = $connection->getQueryGrammar()->substituteBindingsIntoRawSql($query, $connection->prepareBindings($bindings));
                    $digest = hash('sha256', json_encode([$query, $bindings], JSON_THROW_ON_ERROR));
                    $challenge = 'AUTORISER '.$fingerprint.' '.$revision.' '.$digest;
                    if (! $this->confirmQuery($sql, $initial['identity'], $challenge)) {
                        throw new RuntimeException('Requête potentiellement destructive refusée avant exécution.');
                    }
                    $this->assertState($parameters, $fingerprint, $revision);
                    $journal = $this->application->storagePath('logs/deployments.log');
                    if (is_link($journal) || file_put_contents($journal, '['.date('c').'] Autorisation humaine reçue cible='.$fingerprint.' révision='.$revision.' sql_sha256='.$digest."\n", FILE_APPEND | LOCK_EX) === false) {
                        throw new RuntimeException('Journalisation de l’autorisation impossible.');
                    }
                }
            } finally {
                $checking = false;
            }
        };
        foreach ($this->database->getConnections() as $connection) {
            $connection->beforeExecuting($guard);
        }
        $this->events->listen(ConnectionEstablished::class, function (ConnectionEstablished $event) use ($guard): void {
            $event->connection->beforeExecuting($guard);
        });

        $arguments = [
            '--database' => $initial['connection'], '--force' => true, '--no-interaction' => true,
        ];
        if ($preview) {
            $arguments['--pretend'] = true;
        }

        return $this->kernel->call('migrate', $arguments, new ConsoleOutput);
    }

    public static function requiresApproval(string $query): bool
    {
        if (self::isReadOnly($query)) {
            return false;
        }
        $query = trim($query);
        if (str_contains(rtrim($query, "; \t\r\n"), ';')) {
            return true;
        }
        if (preg_match('/\Acreate\s+(?:table|(?:unique\s+)?index)\b/i', $query)) {
            return false;
        }
        if (preg_match('/\Ainsert\s+into\b/i', $query) && ! preg_match('/\bupdate\b/i', $query)) {
            return false;
        }
        if (preg_match('/\Aalter\s+table\s+(?:`[^`]+`|[a-z0-9_.]+)\s+add\b/i', $query)
            && ! preg_match('/\b(drop|modify|change|rename|convert|discard|truncate|replace|update)\b/i', $query)) {
            return false;
        }

        return true;
    }

    private static function isReadOnly(string $query): bool
    {
        return preg_match('/\A\s*(select|show)\b/i', $query)
            && ! str_contains(rtrim($query, "; \t\r\n"), ';');
    }

    private static function assertWriteScope(string $query, string $database): void
    {
        $literalsAndComments = <<<'REGEX'
~'(?:''|\\.|[^'\\])*'|/\*.*?\*/|--[\t ][^\r\n]*|\#[^\r\n]*~s
REGEX;
        $code = preg_replace_callback($literalsAndComments, function (array $match): string {
            if (str_starts_with($match[0], '/*!')) {
                return preg_replace('~\A/\*![0-9]*\s*~', ' ', substr($match[0], 0, -2));
            }

            return ' ';
        }, $query);
        if (preg_match('/\A\s*(?:use|grant|revoke)\b|\b(?:create|alter|drop)\s+(?:database|schema|user|role|server|tablespace)\b/i', $code)) {
            throw new RuntimeException('Opération globale ou changement de schéma hors de la base vérifiée ; écriture refusée.');
        }
        $identifier = '(?:`(?:``|[^`])+`|"(?:""|[^"])+"|[a-zA-Z_][a-zA-Z0-9_$]*)';
        preg_match_all('~('.$identifier.')\s*\.\s*(?:'.$identifier.'|\*)~', $code, $matches);
        foreach ($matches[1] as $qualifier) {
            $qualifier = str_replace(['``', '""'], ['`', '"'], trim($qualifier, '`"'));
            if ($qualifier !== $database) {
                throw new RuntimeException('Identifiant SQL qualifié hors de la base vérifiée ou ambigu ; écriture refusée.');
            }
        }
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array<string, mixed>
     */
    private function assertState(array $parameters, string $fingerprint, string $revision): array
    {
        $report = $this->preflight->inspect($parameters);
        if (! hash_equals($fingerprint, $report['fingerprint']) || ! $report['maintenance']) {
            throw new RuntimeException('Configuration, cible ou maintenance modifiée ; migration refusée.');
        }
        $this->assertRevision($revision);

        return $report;
    }

    protected function assertRevision(string $revision): void
    {
        $head = new Process(['git', 'rev-parse', 'HEAD'], $this->application->basePath());
        $status = new Process(['git', 'status', '--porcelain'], $this->application->basePath());
        $head->mustRun();
        $status->mustRun();
        if (trim($head->getOutput()) !== $revision || trim($status->getOutput()) !== '') {
            throw new RuntimeException('Révision ou code modifié ; migration refusée.');
        }
    }

    /** @param array<string, mixed> $identity */
    protected function confirmQuery(string $sql, array $identity, string $challenge): bool
    {
        fwrite(STDOUT, 'Requête réelle potentiellement destructive ; cible='.json_encode($identity, JSON_THROW_ON_ERROR)."\n".$sql."\n");
        if (! stream_isatty(STDIN)) {
            return false;
        }
        fwrite(STDOUT, 'Après revue, saisir exactement :'."\n".$challenge."\n> ");

        return rtrim((string) fgets(STDIN), "\r\n") === $challenge;
    }
}
