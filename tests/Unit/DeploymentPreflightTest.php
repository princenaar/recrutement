<?php

use App\Services\DeploymentPreflight;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->runtime = sys_get_temp_dir().'/recrutement-preflight-'.bin2hex(random_bytes(8));
    mkdir($this->runtime.'/public/build', 0700, true);
    file_put_contents($this->runtime.'/public/build/app.css', 'fixture');
    file_put_contents($this->runtime.'/public/build/manifest.json', json_encode([
        'app.css' => ['file' => 'app.css'],
    ]));
    $this->parameters = [
        'DEPLOY_DB_DATABASE' => 'production_fixture',
        'DEPLOY_DB_SERVER_UUID' => 'fixture-uuid',
        'DEPLOY_BACKUP_REFERENCE' => 'backup-fixture',
        'DEPLOY_WRITERS_STOPPED' => '1',
    ];
    $this->environment = [];
    foreach (['APP_ENV', ...array_keys($this->parameters)] as $key) {
        $this->environment[$key] = getenv($key);
        putenv($key);
    }
    $this->application = new Application($this->runtime);
    $this->application->instance('env', 'production');
    $this->config = new Repository([
        'app' => ['maintenance' => ['driver' => 'file'], 'url' => 'https://fixture.invalid',
            'debug' => false, 'key' => 'never-print-this-key'],
        'database' => ['default' => 'mysql', 'connections' => ['mysql' => [
            'driver' => 'mysql', 'host' => 'fixture-host', 'port' => '3306',
            'database' => 'production_fixture', 'password' => 'never-print-this-password',
        ]]],
        'cache' => ['default' => 'array', 'stores' => ['array' => ['driver' => 'array']]],
    ]);
    $this->application->instance('config', $this->config);
    $maintenance = $this->createMock(MaintenanceMode::class);
    $maintenance->method('active')->willReturn(false);
    $this->application->instance(MaintenanceMode::class, $maintenance);
    $this->identity = (object) ['db' => 'production_fixture', 'uuid' => 'fixture-uuid',
        'host' => 'fixture-server', 'port' => 3306, 'version' => '8.0-fixture'];
    $this->repositoryExists = true;
    $this->applied = ['migration_a'];
    $this->migrations = ['migration_a' => 'a.php'];
    $this->connectionFailure = false;
    $pdo = $this->createMock(PDO::class);
    $pdo->method('getAttribute')->with(PDO::ATTR_DRIVER_NAME)->willReturn('mysql');
    $schema = $this->createMock(SchemaBuilder::class);
    $schema->method('hasTable')->with('migrations')->willReturnCallback(fn () => $this->repositoryExists);
    $query = $this->createMock(QueryBuilder::class);
    $query->method('useWritePdo')->willReturnSelf();
    $query->method('pluck')->with('migration')->willReturnCallback(fn () => new Collection($this->applied));
    $this->connection = $this->getMockBuilder(MySqlConnection::class)
        ->setConstructorArgs([null, 'production_fixture'])
        ->onlyMethods(['getPdo', 'selectOne', 'getSchemaBuilder', 'table', 'statement', 'affectingStatement'])->getMock();
    $this->connection->method('getPdo')->willReturn($pdo);
    $this->connection->method('selectOne')->with(
        'SELECT DATABASE() AS db, @@server_uuid AS uuid, @@hostname AS host, @@port AS port, VERSION() AS version', [], false,
    )->willReturnCallback(fn () => $this->identity);
    $this->connection->method('getSchemaBuilder')->willReturn($schema);
    $this->connection->method('table')->with('migrations')->willReturn($query);
    $this->connection->expects($this->never())->method('statement');
    $this->connection->expects($this->never())->method('affectingStatement');
    $this->database = $this->createMock(DatabaseManager::class);
    $this->database->method('connection')->willReturnCallback(function () {
        if ($this->connectionFailure) {
            throw new RuntimeException('mysql://user:secret@host/database');
        }

        return $this->connection;
    });
    $this->migrator = $this->createMock(Migrator::class);
    $this->migrator->method('getMigrationFiles')->willReturnCallback(fn () => $this->migrations);
    $this->guard = new DeploymentPreflight($this->database, $this->config, $this->application, $this->migrator);
});

afterEach(function () {
    foreach ($this->environment as $key => $value) {
        putenv($value === false ? $key : $key.'='.$value);
    }
    (new Filesystem)->deleteDirectory($this->runtime);
});

test('it reports only the observed target and a stable configuration fingerprint', function () {
    $report = $this->guard->inspect($this->parameters, true);
    expect($report['identity']['database'])->toBe('production_fixture')
        ->and($report['identity']['server_uuid'])->toBe('fixture-uuid')
        ->and($report['fingerprint'])->toMatch('/^[a-f0-9]{64}$/')
        ->and($report)->toBe($this->guard->inspect($this->parameters, true))
        ->and(json_encode($report))->not->toContain('never-print');
});

test('database URLs are resolved without leaking credentials', function () {
    $this->config->set('database.connections.mysql.database', 'raw-value-is-not-effective');
    $this->config->set('database.connections.mysql.url', 'mysql://private-user:private-password@url-host:3307/production_fixture');
    $report = $this->guard->inspect($this->parameters);
    expect(json_encode($report))->not->toContain('private-user', 'private-password');
    $this->config->set('database.connections.mysql.url', 'mysql://private-user:private-password@other-host:3307/production_fixture');
    expect($report['fingerprint'])->not->toBe($this->guard->inspect($this->parameters)['fingerprint']);
});

test('unsafe configurations are refused', function (string $key, mixed $value) {
    $this->config->set($key, $value);
    expect(fn () => $this->guard->inspect($this->parameters))->toThrow(RuntimeException::class);
})->with([
    'debug enabled' => ['app.debug', true],
    'missing key' => ['app.key', ''],
    'cache maintenance' => ['app.maintenance.driver', 'cache'],
    'multiple hosts' => ['database.connections.mysql.host', ['one', 'two']],
    'read replica' => ['database.connections.mysql.read', ['host' => 'other']],
    'write override' => ['database.connections.mysql.write', ['database' => 'other']],
    'sqlite' => ['database.connections.mysql.driver', 'sqlite'],
    'mariadb' => ['database.connections.mysql.driver', 'mariadb'],
    'another URL database' => ['database.connections.mysql.url', 'mysql://user:secret@fixture-host/other'],
    'remote cache' => ['cache.stores.array.driver', 'redis'],
    'unverified cache connection' => ['cache', ['default' => 'database', 'stores' => ['database' => [
        'driver' => 'database', 'connection' => 'not-configured',
    ]]]],
    'URL credentials' => ['app.url', 'https://user:secret@fixture.invalid'],
    'URL parameters' => ['app.url', 'https://fixture.invalid?secret=value'],
    'invalid URL' => ['app.url', 'not-a-url'],
]);

test('a nonproduction environment is refused before connecting', function () {
    $this->application->instance('env', 'local');
    $database = $this->createMock(DatabaseManager::class);
    $database->expects($this->never())->method('connection');
    $guard = new DeploymentPreflight($database, $this->config, $this->application, $this->migrator);
    expect(fn () => $guard->inspect($this->parameters))->toThrow(RuntimeException::class, 'production');
});

test('the observed database and server must match the authorized target', function (string $property) {
    $this->identity->{$property} = 'another-target';
    expect(fn () => $this->guard->inspect($this->parameters))
        ->toThrow(RuntimeException::class, 'Datasource inaccessible, ambiguë ou différente de la cible autorisée.');
})->with(['database' => ['db'], 'server' => ['uuid']]);

test('connection errors never expose credentials', function () {
    $this->connectionFailure = true;
    expect(fn () => $this->guard->inspect($this->parameters))
        ->toThrow(RuntimeException::class, 'Datasource inaccessible, ambiguë ou différente de la cible autorisée.');
});

test('a missing migration repository cannot be initialized by the deployment', function () {
    $this->repositoryExists = false;
    expect(fn () => $this->guard->inspect($this->parameters))->toThrow(RuntimeException::class, 'Schéma de migrations non vérifié');
});

test('pending migrations prevent opening but allow the initial inspection', function () {
    $this->migrations['migration_b'] = 'b.php';
    $this->guard->inspect($this->parameters);
    expect(fn () => $this->guard->inspect($this->parameters, true))->toThrow(RuntimeException::class, 'Schéma de migrations non vérifié');
});

test('unknown applied migrations prevent deployment', function () {
    $this->applied[] = 'unknown_migration';
    expect(fn () => $this->guard->inspect($this->parameters))->toThrow(RuntimeException::class, 'Schéma de migrations non vérifié');
});

test('an empty migration history cannot trigger schema dump initialization', function () {
    $this->applied = [];
    expect(fn () => $this->guard->inspect($this->parameters))->toThrow(RuntimeException::class, 'Schéma de migrations non vérifié');
});

test('database cache and lock connections must use the verified target', function () {
    $this->config->set('cache', ['default' => 'database', 'prefix' => 'fixture', 'stores' => ['database' => [
        'driver' => 'database', 'connection' => 'mysql', 'lock_connection' => null, 'table' => 'cache',
    ]]]);
    $report = $this->guard->inspect($this->parameters);
    $this->config->set('cache.stores.database.table', 'another_cache');
    expect($report['fingerprint'])->not->toBe($this->guard->inspect($this->parameters)['fingerprint']);
    $this->config->set('cache.stores.database.lock_connection', 'unverified');
    expect(fn () => $this->guard->inspect($this->parameters))->toThrow(RuntimeException::class);
});

test('cache targeting a different database is refused', function () {
    $this->config->set('database.connections.other', [
        'driver' => 'mysql', 'host' => 'fixture-host', 'database' => 'other_database',
    ]);
    $this->config->set('cache', ['default' => 'database', 'stores' => ['database' => [
        'driver' => 'database', 'connection' => 'other',
    ]]]);
    expect(fn () => $this->guard->inspect($this->parameters))->toThrow(RuntimeException::class, 'Datasource');
});

test('invalid or incomplete assets prevent opening', function (string $manifest) {
    file_put_contents($this->runtime.'/public/build/manifest.json', $manifest);
    expect(fn () => $this->guard->inspect($this->parameters))->toThrow(RuntimeException::class, 'Manifeste Vite');
})->with(['invalid JSON' => ['invalid'], 'empty' => ['{}'], 'missing file' => ['{"app":{"file":"missing.css"}}'],
    'outside build' => ['{"app":{"file":"../outside.css"}}']]);

test('a Vite development marker prevents deployment', function () {
    file_put_contents($this->runtime.'/public/hot', 'http://localhost:5173');
    expect(fn () => $this->guard->inspect($this->parameters))->toThrow(RuntimeException::class, 'Manifeste Vite');
});

test('provider manifests can be cleared without booting or deleting configuration', function () {
    mkdir($this->runtime.'/bootstrap/cache', 0700, true);
    foreach (['services.php', 'packages.php', 'config.php'] as $file) {
        file_put_contents($this->runtime.'/bootstrap/cache/'.$file, '<?php throw new RuntimeException("obsolete provider");');
    }
    $application = $this->createMock(Application::class);
    $application->method('getCachedServicesPath')->willReturn($this->runtime.'/bootstrap/cache/services.php');
    $application->method('getCachedPackagesPath')->willReturn($this->runtime.'/bootstrap/cache/packages.php');
    $application->expects($this->never())->method('bootstrapWith');
    DeploymentPreflight::clearProviderManifests($application);
    expect(file_exists($this->runtime.'/bootstrap/cache/services.php'))->toBeFalse()
        ->and(file_exists($this->runtime.'/bootstrap/cache/packages.php'))->toBeFalse()
        ->and(file_exists($this->runtime.'/bootstrap/cache/config.php'))->toBeTrue();
});

test('provider manifest directories are refused', function () {
    mkdir($this->runtime.'/bootstrap/cache/services.php', 0700, true);
    $application = $this->createMock(Application::class);
    $application->method('getCachedServicesPath')->willReturn($this->runtime.'/bootstrap/cache/services.php');
    $application->method('getCachedPackagesPath')->willReturn($this->runtime.'/bootstrap/cache/packages.php');
    expect(fn () => DeploymentPreflight::clearProviderManifests($application))
        ->toThrow(RuntimeException::class, 'Nettoyage des manifests providers impossible.');
});

test('deployment parameters are read independently of cached configuration and external values win', function () {
    file_put_contents($this->runtime.'/.env', implode("\n", array_map(
        fn (string $key, string $value) => $key.'='.$value, array_keys($this->parameters), $this->parameters,
    )));
    expect(DeploymentPreflight::parameters($this->runtime))->toBe($this->parameters);
    putenv('DEPLOY_BACKUP_REFERENCE=external-backup');
    expect(DeploymentPreflight::parameters($this->runtime)['DEPLOY_BACKUP_REFERENCE'])->toBe('external-backup');
    putenv('DEPLOY_WRITERS_STOPPED=');
    expect(fn () => DeploymentPreflight::parameters($this->runtime))->toThrow(RuntimeException::class, 'DEPLOY_WRITERS_STOPPED');
});

test('operational preconditions are mandatory', function (string $key, string $value) {
    foreach ($this->parameters as $parameter => $default) {
        putenv($parameter.'='.$default);
    }
    file_put_contents($this->runtime.'/.env', '');
    putenv($key.'='.$value);
    expect(fn () => DeploymentPreflight::parameters($this->runtime))->toThrow(RuntimeException::class);
})->with([
    'database' => ['DEPLOY_DB_DATABASE', ''], 'server' => ['DEPLOY_DB_SERVER_UUID', ''],
    'backup' => ['DEPLOY_BACKUP_REFERENCE', ''], 'writers' => ['DEPLOY_WRITERS_STOPPED', '0'],
    'control character' => ['DEPLOY_BACKUP_REFERENCE', "backup\tinjected"],
]);

test('invalid dotenv errors are sanitized', function () {
    file_put_contents($this->runtime.'/.env', "PRIVATE SECRET=do-not-print\n");
    expect(fn () => DeploymentPreflight::parameters($this->runtime))
        ->toThrow(RuntimeException::class, 'Paramètres de déploiement illisibles.');
});
