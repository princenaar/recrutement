<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class DeployScriptTest extends TestCase
{
    private string $runtime;

    private string $bash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bash = getenv('DEPLOY_TEST_BASH') ?: (PHP_OS_FAMILY === 'Windows' ? 'C:/Program Files/Git/bin/bash.exe' : '/bin/bash');
        if (! is_file($this->bash)) {
            $this->fail('Bash est requis ; définir DEPLOY_TEST_BASH si nécessaire.');
        }
        $this->runtime = sys_get_temp_dir().'/recrutement-deploy-'.bin2hex(random_bytes(8));
        foreach (['', '/bin', '/tmp', '/vendor', '/app/Services', '/public/build', '/storage/framework/cache', '/storage/framework/views', '/storage/logs', '/bootstrap/cache'] as $suffix) {
            if (! is_dir($this->runtime.$suffix)) {
                mkdir($this->runtime.$suffix, 0700, true);
            }
        }
        copy(dirname(__DIR__, 2).'/deploy.sh', $this->runtime.'/deploy.sh');
        foreach (['.env', 'artisan', 'vendor/autoload.php', 'app/Services/DeploymentPreflight.php', 'app/Services/DeploymentMigrationGuard.php', 'public/build/manifest.json'] as $file) {
            file_put_contents($this->runtime.'/'.$file, 'fixture');
        }
        $functions = str_replace("\r\n", "\n", $this->fakeExecutable());
        $functions = substr($functions, strpos($functions, 'name="$(basename "$0")"'));
        $functions = str_replace('name="$(basename "$0")"', 'local name="$1"; shift', $functions);
        $functions = str_replace(['$PPID', 'exit '], ['$$', 'return '], $functions);
        $functions = preg_replace_callback('/\$([A-Z][A-Z_0-9]*|[12])\b/', fn (array $match): string => '${'.$match[1].':-}', $functions);
        $functions = "fake_command() {\n".$functions."\n}\n";
        foreach (['git', 'php', 'composer', 'curl', 'tty'] as $command) {
            $functions .= $command.'() { fake_command '.$command.' "$@"; }'."\n";
        }
        file_put_contents($this->runtime.'/functions.sh', $functions);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->runtime);
        parent::tearDown();
    }

    public function test_success_opens_only_after_all_internal_checks_and_records_revisions(): void
    {
        $process = $this->deploy();
        $this->assertSame(0, $process->getExitCode(), $this->processOutput($process));
        $trace = $this->trace();
        $this->assertSame(1, substr_count($trace, "artisan up\n"));
        $this->assertLessThan(strpos($trace, "artisan up\n"), strpos($trace, "artisan view:cache\n"));
        $this->assertLessThan(strpos($trace, "artisan up\n"), strpos($trace, "preflight ready=1\n"));
        $this->assertLessThan(strpos($trace, "composer install\n"), strpos($trace, "preflight ready=0\n"));
        $this->assertStringContainsString('--no-scripts --no-plugins', file_get_contents($this->runtime.'/composer-arguments'));
        $this->assertStringContainsString('composer run-script', $trace);
        $this->assertStringNotContainsString('artisan optimize', $trace);
        $this->assertStringNotContainsString('artisan cache:clear', $trace);
        $this->assertStringNotContainsString('artisan storage:link', $trace);
        $this->assertStringNotContainsString('pdf:', $trace);
        $this->assertStringContainsString("artisan queue:restart\n", $trace);
        $this->assertStringNotContainsString('--location', file_get_contents($this->runtime.'/curl-arguments'));
        $this->assertStringContainsString('--max-time 15', file_get_contents($this->runtime.'/curl-arguments'));
        $this->assertFileDoesNotExist($this->maintenancePath());
        $this->assertDirectoryDoesNotExist($this->lockPath());
        $journal = file_get_contents($this->runtime.'/storage/logs/deployments.log');
        $this->assertStringContainsString('avant='.str_repeat('a', 40), $journal);
        $this->assertStringContainsString('après='.str_repeat('b', 40), $journal);
        $this->assertStringContainsString('sauvegarde=backup-fixture', $journal);
        $this->assertStringContainsString('phase=termine Résultat code=0', $journal);
        $this->assertSame('0022', trim(file_get_contents($this->runtime.'/git-umask')));
    }

    #[DataProvider('originRemotes')]
    public function test_only_the_expected_https_and_ssh_remotes_are_accepted(string $remote, int $code): void
    {
        $process = $this->deploy(['REMOTE_URL' => $remote]);
        $this->assertSame($code, $process->getExitCode(), $this->processOutput($process));
        if ($code !== 0) {
            $this->assertStringNotContainsString('artisan ', $this->trace());
            $this->assertStringNotContainsString('git fetch', $this->trace());
        }
    }

    /** @return array<string, array{string, int}> */
    public static function originRemotes(): array
    {
        return [
            'HTTPS' => ['https://github.com/princenaar/recrutement.git', 0],
            'SSH' => ['git@github.com:princenaar/recrutement.git', 0],
            'another repository' => ['git@github.com:princenaar/DGP-Demat.git', 1],
            'credentials in URL' => ['https://secret@github.com/princenaar/recrutement.git', 1],
        ];
    }

    public function test_foreign_key_cascade_definitions_do_not_require_destructive_confirmation(): void
    {
        $process = $this->deploy(['PREVIEW_SQL' => 'ALTER TABLE child ADD FOREIGN KEY (parent_id) REFERENCES parent(id) ON DELETE CASCADE;']);
        $this->assertSame(0, $process->getExitCode(), $this->processOutput($process));
    }

    #[DataProvider('failurePhases')]
    public function test_failure_before_opening_never_calls_up_and_preserves_exit_status(string $operation, bool $maintenance): void
    {
        $process = $this->deploy(['FAIL_AT' => $operation]);
        $this->assertSame(37, $process->getExitCode(), $this->processOutput($process));
        $this->assertStringNotContainsString("artisan up\n", $this->trace());
        $this->assertSame($maintenance, is_file($this->maintenancePath()));
        $this->assertDirectoryDoesNotExist($this->lockPath());
        $this->assertStringContainsString('Résultat code=37', $this->processOutput($process));
    }

    /** @return array<string, array{string, bool}> */
    public static function failurePhases(): array
    {
        return [
            'fetch' => ['git fetch', false],
            'fast forward' => ['git merge', true],
            'activation' => ['artisan down', true],
            'composer installation' => ['composer install', true],
            'composer manifests' => ['bootstrap-manifests', true],
            'composer hooks' => ['composer run-script', true],
            'configuration clear' => ['artisan config:clear', true],
            'platform requirements' => ['composer check-platform-reqs', true],
            'migration status' => ['artisan migrate:status', true],
            'migration preview' => ['preview', true],
            'migration execution' => ['artisan migrate', true],
            'queue restart' => ['artisan queue:restart', true],
            'configuration cache' => ['artisan config:cache', true],
            'event cache' => ['artisan event:cache', true],
            'route cache' => ['artisan route:cache', true],
            'view cache' => ['artisan view:cache', true],
        ];
    }

    #[DataProvider('interruptions')]
    public function test_interruptions_preserve_maintenance_and_release_the_owned_lock(string $operation, string $signal, int $code): void
    {
        $process = $this->deploy(['SIGNAL_AT' => $operation, 'SIGNAL_NAME' => $signal]);
        $this->assertSame($code, $process->getExitCode(), $this->processOutput($process));
        $this->assertFileExists($this->maintenancePath());
        $this->assertDirectoryDoesNotExist($this->lockPath());
        if ($operation !== 'curl') {
            $this->assertStringNotContainsString("artisan up\n", $this->trace());
        } else {
            $this->assertSame(2, substr_count($this->trace(), "artisan down\n"));
        }
    }

    /** @return array<string, array{string, string, int}> */
    public static function interruptions(): array
    {
        return [
            'INT before migrations' => ['composer install', 'INT', 130],
            'TERM before migrations' => ['composer install', 'TERM', 143],
            'INT during migration' => ['artisan migrate', 'INT', 130],
            'TERM after opening' => ['curl', 'TERM', 143],
        ];
    }

    #[DataProvider('healthFailures')]
    public function test_failed_opening_or_health_reactivates_maintenance(array $environment, int $code): void
    {
        $process = $this->deploy($environment);
        $this->assertSame($code, $process->getExitCode(), $this->processOutput($process));
        $this->assertFileExists($this->maintenancePath());
        $this->assertSame(2, substr_count($this->trace(), "artisan down\n"));
        $this->assertDirectoryDoesNotExist($this->lockPath());
        $this->assertStringContainsString('reprise opérateur', $this->processOutput($process));
    }

    /** @return array<string, array{array<string, string>, int}> */
    public static function healthFailures(): array
    {
        return [
            'up partial failure' => [['FAIL_UP' => '1'], 37],
            'HTTP transport' => [['FAIL_AT' => 'curl'], 37],
            'HTTP redirect' => [['HTTP_STATUS' => '302'], 1],
            'HTTP error' => [['HTTP_STATUS' => '500'], 1],
            'down failure uses file fallback' => [['HTTP_STATUS' => '500', 'FAIL_REDOWN' => '1'], 1],
        ];
    }

    public function test_existing_maintenance_is_never_modified_or_opened(): void
    {
        file_put_contents($this->maintenancePath(), 'original maintenance payload');
        $process = $this->deploy();
        $this->assertSame(0, $process->getExitCode(), $this->processOutput($process));
        $this->assertSame('original maintenance payload', file_get_contents($this->maintenancePath()));
        $this->assertStringNotContainsString("artisan up\n", $this->trace());
        $this->assertStringNotContainsString("artisan down\n", $this->trace());
        $this->assertStringNotContainsString("curl\n", $this->trace());
        $this->assertStringContainsString('Prêt, maintenance conservée', $this->processOutput($process));
    }

    public function test_stale_provider_manifests_are_cleared_before_the_first_boot_after_composer(): void
    {
        $process = $this->deploy(['STALE_PROVIDERS' => '1']);
        $this->assertSame(0, $process->getExitCode(), $this->processOutput($process));
        $trace = $this->trace();
        $this->assertLessThan(strpos($trace, "bootstrap-manifests\n"), strpos($trace, "composer install\n"));
        $this->assertFileDoesNotExist($this->runtime.'/obsolete-providers');
        $afterComposer = substr($trace, strpos($trace, "composer install\n") + strlen("composer install\n"));
        $this->assertStringStartsWith("composer check-platform-reqs\nbootstrap-manifests\npreflight ready=0\n", $afterComposer);
    }

    public function test_existing_maintenance_is_preserved_on_failure_too(): void
    {
        file_put_contents($this->maintenancePath(), 'original maintenance payload');
        $process = $this->deploy(['FAIL_AT' => 'artisan migrate']);
        $this->assertSame(37, $process->getExitCode(), $this->processOutput($process));
        $this->assertSame('original maintenance payload', file_get_contents($this->maintenancePath()));
        $this->assertStringNotContainsString("artisan up\n", $this->trace());
        $this->assertStringNotContainsString("artisan down\n", $this->trace());
    }

    public function test_an_unverified_initial_target_prevents_all_application_writes(): void
    {
        $process = $this->deploy(['FAIL_AT' => 'preflight ready=0']);
        $this->assertSame(1, $process->getExitCode(), $this->processOutput($process));
        $this->assertStringNotContainsString('artisan ', $this->trace());
        $this->assertStringNotContainsString('composer ', $this->trace());
        $this->assertStringNotContainsString('git fetch', $this->trace());
        $this->assertDirectoryDoesNotExist($this->lockPath());
    }

    public function test_a_manifest_missing_from_the_target_prevents_opening(): void
    {
        $process = $this->deploy(['REMOVE_MANIFEST' => '1']);
        $this->assertSame(1, $process->getExitCode(), $this->processOutput($process));
        $this->assertFileExists($this->maintenancePath());
        $this->assertStringNotContainsString("artisan up\n", $this->trace());
    }

    public function test_a_failed_file_fallback_reports_the_critical_state_without_masking_the_failure(): void
    {
        $process = $this->deploy(['HTTP_STATUS' => '500', 'FAIL_RECOVERY' => '1']);
        $this->assertSame(1, $process->getExitCode(), $this->processOutput($process));
        $this->assertStringContainsString('CRITIQUE : maintenance impossible', $this->processOutput($process));
        $this->assertSame(1, substr_count($this->trace(), "artisan up\n"));
        $this->assertDirectoryDoesNotExist($this->lockPath());
    }

    public function test_an_existing_lock_is_not_removed(): void
    {
        mkdir($this->lockPath());
        file_put_contents($this->lockPath().'/pid', 'another-process');
        $process = $this->deploy();
        $this->assertSame(1, $process->getExitCode(), $this->processOutput($process));
        $this->assertSame('another-process', file_get_contents($this->lockPath().'/pid'));
        $this->assertStringNotContainsString("artisan down\n", $this->trace());
    }

    public function test_a_replaced_lock_is_not_removed_even_after_failure(): void
    {
        $process = $this->deploy(['REPLACE_LOCK' => '1', 'FAIL_AT' => 'artisan migrate']);
        $this->assertSame(37, $process->getExitCode(), $this->processOutput($process));
        $this->assertSame('another-process', file_get_contents($this->lockPath().'/pid'));
    }

    #[DataProvider('configurationDrift')]
    public function test_configuration_or_internal_check_failure_prevents_opening(string $drift): void
    {
        $process = $this->deploy(['DRIFT_AT' => $drift]);
        $this->assertSame(1, $process->getExitCode(), $this->processOutput($process));
        $this->assertStringNotContainsString("artisan up\n", $this->trace());
        $this->assertSame($drift !== 'uncached', is_file($this->maintenancePath()));
        $this->assertDirectoryDoesNotExist($this->lockPath());
    }

    /** @return array<string, array{string}> */
    public static function configurationDrift(): array
    {
        return ['uncached' => ['uncached'], 'Git' => ['git'], 'clear' => ['clear'], 'cache' => ['cache'], 'pending' => ['ready']];
    }

    /** @param array<string, string> $environment */
    private function deploy(array $environment = [], string $input = ''): Process
    {
        $process = new Process([$this->bash, '--noprofile', '--norc', '-c',
            'umask 022; export PATH="/usr/bin:/bin"; deployment_script="$2"; source "$1"; set --; source "$deployment_script"', 'deploy-fixture',
            $this->unixPath($this->runtime).'/functions.sh', $this->unixPath($this->runtime).'/deploy.sh'], $this->runtime, array_replace([
                'TMPDIR' => $this->unixPath($this->runtime).'/tmp', 'USER' => 'deploy-test',
                'FAKE_ROOT' => $this->unixPath($this->runtime),
            ], $environment), $input, 60);
        $process->run();

        return $process;
    }

    private function trace(): string
    {
        return is_file($this->runtime.'/trace') ? file_get_contents($this->runtime.'/trace') : '';
    }

    private function processOutput(Process $process): string
    {
        return $process->getOutput().$process->getErrorOutput();
    }

    private function maintenancePath(): string
    {
        return $this->runtime.'/storage/framework/down';
    }

    private function lockPath(): string
    {
        return $this->runtime.'/tmp/recrutement-deploy-test-deploy.lock';
    }

    private function unixPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if (preg_match('/^([A-Za-z]):/', $path, $matches)) {
            $path = '/'.strtolower($matches[1]).substr($path, 2);
        }

        return $path;
    }

    private function fakeExecutable(): string
    {
        return <<<'BASH'
#!/usr/bin/env bash
set -e
name="$(basename "$0")"
[[ -f "$FAKE_ROOT/process-pid" ]] || printf '%s' "$PPID" > "$FAKE_ROOT/process-pid"
operation="$name"
if [[ "$name" == "php" ]]; then
    if [[ "$1" == "artisan" ]]; then
        operation="artisan $2"
        if [[ "$2" == "migrate" && "$*" == *"--pretend"* ]]; then operation="preview"; fi
    elif [[ "$1" == "-r" ]]; then
        if [[ "$2" == *'DeploymentMigrationGuard'* ]]; then
            if [[ "$DEPLOY_PREVIEW" == "1" ]]; then operation="preview"; else operation="artisan migrate"; fi
        else
            operation="php-version"
        fi
    else
        if [[ "$DEPLOY_CLEAR_MANIFESTS" == "1" ]]; then
            operation="bootstrap-manifests"
        else
            operation="preflight ready=$DEPLOY_REQUIRE_READY"
        fi
    fi
elif [[ "$name" == "git" || "$name" == "composer" ]]; then
    operation="$name $1"
fi
printf '%s\n' "$operation" >> "$FAKE_ROOT/trace"
if [[ "$SIGNAL_AT" == "$operation" ]]; then
    kill -s "$SIGNAL_NAME" "$(<"$FAKE_ROOT/process-pid")"
    exit 0
fi
if [[ "$FAIL_AT" == "$operation" ]]; then exit 37; fi
case "$name" in
git)
    case "$1" in
        branch) printf 'main' ;;
        remote) printf '%s' "${REMOTE_URL:-git@github.com:princenaar/recrutement.git}" ;;
        status)
            if [[ "$CODE_CHANGED" == "1" && -f "$FAKE_ROOT/confirmed" ]]; then printf ' M fixture'; fi ;;
        rev-parse)
            if [[ "$2" == "--is-inside-work-tree" ]]; then
                printf 'true'
            elif [[ "$REVISION_CHANGED" == "1" && -f "$FAKE_ROOT/confirmed" ]]; then
                printf '%040d' 3 | tr ' ' c
            elif [[ "$2" == "origin/main" || -f "$FAKE_ROOT/updated" ]]; then
                printf 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'
            else
                printf 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
            fi ;;
        merge)
            touch "$FAKE_ROOT/updated"
            umask > "$FAKE_ROOT/git-umask"
            if [[ "$REMOVE_MANIFEST" == "1" ]]; then rm -f "$FAKE_ROOT/public/build/manifest.json"; fi
            if [[ "$REPLACE_LOCK" == "1" ]]; then printf 'another-process' > "$TMPDIR/recrutement-deploy-test-deploy.lock/pid"; fi ;;
        fetch|merge-base) ;;
        *) exit 97 ;;
    esac ;;
composer)
    if [[ "$1" == "install" && "$STALE_PROVIDERS" == "1" ]]; then touch "$FAKE_ROOT/obsolete-providers"; fi
    printf '%s\n' "$*" >> "$FAKE_ROOT/composer-arguments" ;;
curl)
    printf '%s\n' "$*" > "$FAKE_ROOT/curl-arguments"
    printf '%s' "$HTTP_STATUS"
    if [[ -z "$HTTP_STATUS" ]]; then printf '200'; fi ;;
tty)
    [[ "$TTY_AVAILABLE" == "1" ]] || exit 1
    touch "$FAKE_ROOT/confirmed" ;;
php)
    case "$operation" in
        php-version) ;;
        hash) cat >/dev/null; printf '%s' "$FAKE_SQL_HASH" ;;
        bootstrap-manifests) cat >/dev/null; rm -f "$FAKE_ROOT/obsolete-providers" ;;
        "preflight ready="*)
            cat >/dev/null
            [[ ! -f "$FAKE_ROOT/obsolete-providers" ]] || exit 37
            fingerprint='aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
            if [[ "$DRIFT_AT" == "uncached" && "$APP_CONFIG_CACHE" == *"/uncached.php" ]] \
                || [[ "$DRIFT_AT" == "git" && -f "$FAKE_ROOT/updated" ]] \
                || [[ "$DRIFT_AT" == "clear" && -f "$FAKE_ROOT/cleared" ]] \
                || [[ "$DRIFT_AT" == "cache" && -f "$FAKE_ROOT/cached" ]] \
                || [[ "$DRIFT_AT" == "confirmation" && -f "$FAKE_ROOT/confirmed" ]]; then
                fingerprint='bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'
            fi
            if [[ "$DRIFT_AT" == "ready" && "$DEPLOY_REQUIRE_READY" == "1" ]]; then exit 37; fi
            active=0
            if [[ -f "$FAKE_ROOT/storage/framework/down" ]]; then active=1; fi
            printf '%s\tmysql\t%s\thttps://fixture.invalid\t{"database":"fixture","server_uuid":"fixture-uuid"}\tbackup-fixture\t%s/storage/framework/down' "$fingerprint" "$active" "$FAKE_ROOT" ;;
        "artisan down")
            if [[ "$FAIL_RECOVERY" == "1" && -f "$FAKE_ROOT/opened" ]]; then
                rmdir "$FAKE_ROOT/storage/framework/cache" "$FAKE_ROOT/storage/framework/views" "$FAKE_ROOT/storage/framework"
                exit 37
            fi
            if [[ "$FAIL_REDOWN" == "1" && -f "$FAKE_ROOT/opened" ]]; then exit 37; fi
            printf 'maintenance' > "$FAKE_ROOT/storage/framework/down" ;;
        "artisan up")
            rm -f "$FAKE_ROOT/storage/framework/down"
            touch "$FAKE_ROOT/opened"
            if [[ "$FAIL_UP" == "1" ]]; then exit 37; fi ;;
        preview)
            if [[ -n "$PREVIEW_SQL" ]]; then
                printf '%s' "$PREVIEW_SQL"
            elif [[ "$DESTRUCTIVE" == "1" ]]; then
                if [[ "$SQL_CHANGED" == "1" && -f "$FAKE_ROOT/previewed" ]]; then
                    printf 'TRUNCATE TABLE fixture;'
                else
                    printf 'ALTER TABLE fixture DROP COLUMN obsolete;'
                fi
                touch "$FAKE_ROOT/previewed"
            else
                printf 'ALTER TABLE fixture ADD COLUMN example INT;'
            fi ;;
        "artisan config:clear") touch "$FAKE_ROOT/cleared" ;;
        "artisan config:cache") touch "$FAKE_ROOT/cached" ;;
        "artisan migrate:status"|"artisan migrate"|"artisan queue:restart"|"artisan event:cache"|"artisan route:cache"|"artisan view:cache") ;;
        *) exit 97 ;;
    esac ;;
*) exit 97 ;;
esac
exit 0
BASH;
    }
}
