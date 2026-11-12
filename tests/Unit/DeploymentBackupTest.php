<?php

use App\Services\DeploymentBackup;
use App\Services\DeploymentPreflight;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\MySqlConnection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->runtime = sys_get_temp_dir().'/recrutement-backup-'.bin2hex(random_bytes(8));
    mkdir($this->runtime.'/public_html/app', 0700, true);
    $this->application = new Application($this->runtime.'/public_html/app');
    $this->path = DeploymentBackup::prepare($this->application->basePath(), $this->runtime);
    $this->parameters = ['DEPLOY_BACKUP_REFERENCE' => $this->path];
    $this->fingerprint = str_repeat('a', 64);
    $this->revision = str_repeat('b', 40);
    $this->report = ['fingerprint' => $this->fingerprint, 'maintenance' => true,
        'connection' => 'mysql', 'identity' => ['database' => 'production_fixture', 'server_uid' => 'fixture-uid']];
    $this->config = ['driver' => 'mysql', 'host' => 'fixture-host', 'port' => 3307,
        'database' => 'production_fixture', 'username' => 'fixture-user', 'password' => "secret\"\\\npassword"];
    $this->tables = [(object) ['name' => 'agents', 'engine' => 'InnoDB'], (object) ['name' => 'migrations', 'engine' => 'InnoDB']];
    $this->dump = 'CREATE TABLE '.chr(96).'agents'.chr(96)." (\n);\nCREATE TABLE ".chr(96).'migrations'.chr(96)." (\n);\n-- Dump completed on 2026-11-12 12:00:00\n";
    $this->exitCode = 0;
    $this->afterDump = null;
    $this->processCalls = 0;
    $this->preflight = $this->createMock(DeploymentPreflight::class);
    $this->preflight->method('inspect')->willReturnCallback(fn () => $this->report);
    $this->connection = $this->getMockBuilder(MySqlConnection::class)
        ->setConstructorArgs([null, 'production_fixture'])
        ->onlyMethods(['getConfig', 'select', 'selectOne', 'statement', 'affectingStatement'])->getMock();
    $this->connection->method('getConfig')->willReturnCallback(fn () => $this->config);
    $this->connection->method('select')->willReturnCallback(function (string $sql, array $bindings, bool $read) {
        expect($sql)->toContain('information_schema.TABLES')
            ->and($bindings)->toBe(['production_fixture', 'BASE TABLE'])->and($read)->toBeFalse();

        return $this->tables;
    });
    $this->connection->method('selectOne')->with('SELECT @@socket AS socket', [], false)
        ->willReturn((object) ['socket' => '/fixture/mysql.sock']);
    $this->connection->expects($this->never())->method('statement');
    $this->connection->expects($this->never())->method('affectingStatement');
    $this->database = $this->createMock(DatabaseManager::class);
    $this->database->method('connection')->with('mysql')->willReturn($this->connection);
    $this->backup = $this->getMockBuilder(DeploymentBackup::class)
        ->setConstructorArgs([$this->preflight, $this->database, $this->application])
        ->onlyMethods(['dumpBinary', 'dumpProcess'])->getMock();
    $this->backup->method('dumpBinary')->willReturn('/fixture/mariadb-dump');
    $this->backup->method('dumpProcess')->willReturnCallback(function (array $arguments) {
        $this->processCalls++;
        $this->arguments = $arguments;
        $this->credentials = file_get_contents(dirname($this->path).'/.client.cnf');
        $process = $this->createMock(Process::class);
        $process->method('run')->willReturnCallback(function () {
            file_put_contents($this->path.'.partial', $this->dump);
            if ($this->afterDump !== null) {
                ($this->afterDump)();
            }

            return $this->exitCode;
        });

        return $process;
    });
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->runtime);
});

test('a complete private dump is verified before it is published with an integrity manifest', function () {
    $result = $this->backup->create($this->parameters, $this->fingerprint, $this->revision);
    $manifest = json_decode(file_get_contents(dirname($this->path).'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($result)->toBe(['path' => $this->path, 'sha256' => hash('sha256', $this->dump), 'tables' => 2, 'bytes' => strlen($this->dump)])
        ->and($manifest['sha256'])->toBe(hash_file('sha256', $this->path))
        ->and($manifest['identity'])->toBe($this->report['identity'])
        ->and($manifest['revision'])->toBe($this->revision)
        ->and($manifest['tables'])->toBe(['agents', 'migrations'])
        ->and(json_encode($manifest))->not->toContain('secret', 'fixture-user')
        ->and($this->arguments)->toContain('--single-transaction', '--quick', '--skip-lock-tables', '--routines', '--events', '--triggers', '--host=fixture-host', '--port=3307')
        ->and($this->arguments[1])->toBe('--defaults-file='.dirname($this->path).'/.client.cnf')
        ->and(implode(' ', $this->arguments))->not->toContain('secret')
        ->and($this->credentials)->toContain('password="secret\\"\\\\\\npassword"')
        ->and(file_exists($this->path.'.partial'))->toBeFalse()
        ->and(file_exists(dirname($this->path).'/.client.cnf'))->toBeFalse();
    if (PHP_OS_FAMILY !== 'Windows') {
        expect(fileperms($this->path) & 0777)->toBe(0600)
            ->and(fileperms(dirname($this->path)) & 0777)->toBe(0700);
    }
});

test('backup paths are unique and outside the public application', function () {
    $second = DeploymentBackup::prepare($this->application->basePath(), $this->runtime);
    expect(str_replace('\\', '/', $second))->not->toBe(str_replace('\\', '/', $this->path))
        ->and(str_replace('\\', '/', $second))->toStartWith(str_replace('\\', '/', $this->runtime).'/recrutement-backups/')
        ->and(file_exists($second))->toBeFalse();
});

test('a public home directory is refused', function () {
    expect(fn () => DeploymentBackup::prepare($this->application->basePath(), $this->application->basePath()))
        ->toThrow(RuntimeException::class);
});

test('a home directory located at the web root is refused', function () {
    expect(fn () => DeploymentBackup::prepare($this->application->basePath(), $this->runtime.'/public_html'))
        ->toThrow(RuntimeException::class);
});

test('a backup destination elsewhere inside public_html is refused', function () {
    mkdir($this->runtime.'/public_html/backup', 0700);
    $this->parameters['DEPLOY_BACKUP_REFERENCE'] = $this->runtime.'/public_html/backup/database.sql';
    expect(fn () => $this->backup->create($this->parameters, $this->fingerprint, $this->revision))
        ->toThrow(RuntimeException::class)
        ->and($this->processCalls)->toBe(0);
});

test('unverified targets and inactive maintenance cannot launch a dump', function (string $failure) {
    $this->report[$failure] = $failure === 'maintenance' ? false : str_repeat('c', 64);
    expect(fn () => $this->backup->create($this->parameters, $this->fingerprint, $this->revision))
        ->toThrow(RuntimeException::class)
        ->and($this->processCalls)->toBe(0)
        ->and(file_exists($this->path))->toBeFalse();
})->with(['fingerprint', 'maintenance']);

test('nontransactional or missing tables block the dump', function (bool $empty) {
    $this->tables = $empty ? [] : [(object) ['name' => 'agents', 'engine' => 'MyISAM']];
    expect(fn () => $this->backup->create($this->parameters, $this->fingerprint, $this->revision))
        ->toThrow(RuntimeException::class)
        ->and($this->processCalls)->toBe(0);
})->with([false, true]);

test('connection drift or unsupported connection options block the dump', function (array $change) {
    $this->config = array_replace($this->config, $change);
    expect(fn () => $this->backup->create($this->parameters, $this->fingerprint, $this->revision))
        ->toThrow(RuntimeException::class)->and($this->processCalls)->toBe(0);
})->with([
    'database' => [['database' => 'other_database']],
    'split connection' => [['read' => ['host' => 'other']]],
    'multiple hosts' => [['host' => ['one', 'two']]],
    'unsupported TLS options' => [['options' => [1 => 'fixture']]],
]);

test('a failed empty or incomplete dump never produces a final backup or retains credentials', function (string $failure) {
    if ($failure === 'exit') {
        $this->exitCode = 1;
    } elseif ($failure === 'empty') {
        $this->dump = '';
    } elseif ($failure === 'footer') {
        $this->dump = strstr($this->dump, '-- Dump completed', true);
    } elseif ($failure === 'table') {
        $this->dump = 'CREATE TABLE '.chr(96).'agents'.chr(96)." (\n);\n-- Dump completed on fixture\n";
    } else {
        $this->afterDump = function () {
            $this->report['fingerprint'] = str_repeat('c', 64);
        };
    }
    expect(fn () => $this->backup->create($this->parameters, $this->fingerprint, $this->revision))
        ->toThrow(RuntimeException::class)
        ->and(file_exists($this->path))->toBeFalse()
        ->and(file_exists(dirname($this->path).'/manifest.json'))->toBeFalse()
        ->and(file_exists(dirname($this->path).'/.client.cnf'))->toBeFalse();
})->with(['exit', 'empty', 'footer', 'table', 'target drift']);

test('an existing dump is preserved', function () {
    file_put_contents($this->path, 'ORIGINAL');
    expect(fn () => $this->backup->create($this->parameters, $this->fingerprint, $this->revision))
        ->toThrow(RuntimeException::class)
        ->and(file_get_contents($this->path))->toBe('ORIGINAL')
        ->and($this->processCalls)->toBe(0);
});

test('localhost uses the observed socket and explicit sockets are preserved', function (string $socket) {
    $this->config['host'] = 'localhost';
    $this->config['unix_socket'] = $socket;
    $this->backup->create($this->parameters, $this->fingerprint, $this->revision);
    expect($this->arguments)->toContain('--protocol=SOCKET', '--socket='.($socket ?: '/fixture/mysql.sock'))
        ->not->toContain('--protocol=TCP');
})->with(['', '/explicit/mysql.sock']);
