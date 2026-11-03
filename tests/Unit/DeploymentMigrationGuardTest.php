<?php

use App\Services\DeploymentMigrationGuard;
use App\Services\DeploymentPreflight;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\MySqlConnection;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;

beforeEach(function () {
    $this->runtime = sys_get_temp_dir().'/recrutement-migration-'.bin2hex(random_bytes(8));
    mkdir($this->runtime.'/storage/logs', 0700, true);
    $this->executed = [];
    $this->challenges = [];
    $this->response = null;
    $this->revisionValid = true;
    $this->afterConfirmation = null;
    $this->report = ['fingerprint' => str_repeat('a', 64), 'maintenance' => true,
        'connection' => 'mysql', 'identity' => ['database' => 'fixture', 'server_uuid' => 'fixture-uuid']];
    $pdo = $this->createMock(PDO::class);
    $this->pdo = $pdo;
    $pdo->method('quote')->willReturnCallback(fn (string $value): string => "'".str_replace("'", "''", $value)."'");
    $pdo->method('prepare')->willReturnCallback(function (string $query) {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('setFetchMode')->willReturn(true);
        $statement->method('bindValue')->willReturn(true);
        $statement->method('execute')->willReturnCallback(function () use ($query): bool {
            $this->executed[] = $query;

            return true;
        });
        $statement->method('fetchAll')->willReturn([(object) ['exists' => true]]);
        $statement->method('rowCount')->willReturn(1);

        return $statement;
    });
    $this->connection = new MySqlConnection($pdo, 'fixture', '', ['name' => 'mysql', 'database' => 'fixture']);
    $this->database = $this->createMock(DatabaseManager::class);
    $this->database->method('connection')->with('mysql')->willReturnCallback(fn () => $this->connection);
    $this->database->method('getConnections')->willReturnCallback(fn () => ['mysql' => $this->connection]);
    $this->preflight = $this->createMock(DeploymentPreflight::class);
    $this->preflight->method('inspect')->willReturnCallback(fn () => $this->report);
    $this->events = new Dispatcher;
    $this->kernel = $this->createMock(Kernel::class);
    $this->operation = fn () => $this->connection->statement('CREATE TABLE fixture_new (id INT)');
    $this->kernel->method('call')->willReturnCallback(function (string $command, array $arguments): int {
        expect($command)->toBe('migrate')->and($arguments['--database'])->toBe('mysql')
            ->and($arguments['--force'])->toBeTrue()->and($arguments['--no-interaction'])->toBeTrue();
        if ($arguments['--pretend'] ?? false) {
            $this->connection->pretend($this->operation);
        } else {
            ($this->operation)();
        }

        return 0;
    });
    $this->guard = $this->getMockBuilder(DeploymentMigrationGuard::class)
        ->setConstructorArgs([$this->preflight, $this->database, $this->events, new Application($this->runtime), $this->kernel])
        ->onlyMethods(['assertRevision', 'confirmQuery'])->getMock();
    $this->guard->method('assertRevision')->willReturnCallback(function (): void {
        if (! $this->revisionValid) {
            throw new RuntimeException('Révision ou code modifié');
        }
    });
    $this->guard->method('confirmQuery')->willReturnCallback(function (string $sql, array $identity, string $challenge): bool {
        $this->challenges[] = [$sql, $identity, $challenge];
        if ($this->afterConfirmation !== null) {
            ($this->afterConfirmation)();
        }

        return $this->response === 'exact' || $this->response === $challenge;
    });
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->runtime);
});

test('additive SQL executes without destructive consent', function (string $sql) {
    $this->operation = fn () => $this->connection->statement($sql);
    expect($this->guard->run([], str_repeat('a', 64), str_repeat('b', 40)))->toBe(0)
        ->and($this->executed)->toBe([$sql])->and($this->challenges)->toBe([]);
})->with([
    'create' => ['CREATE TABLE fixture_new (id INT)'],
    'index' => ['CREATE INDEX fixture_index ON fixture(id)'],
    'add column' => ['ALTER TABLE fixture ADD COLUMN example INT'],
    'foreign key' => ['ALTER TABLE child ADD FOREIGN KEY (parent_id) REFERENCES parent(id) ON DELETE CASCADE'],
    'migration log' => ['insert into `migrations` (`migration`, `batch`) values ("fixture", 2)'],
]);

test('potentially destructive SQL requires human confirmation before PDO executes', function (string $sql) {
    $this->operation = fn () => $this->connection->statement($sql);
    expect(fn () => $this->guard->run([], str_repeat('a', 64), str_repeat('b', 40)))
        ->toThrow(RuntimeException::class, 'refusée avant exécution');
    expect($this->executed)->toBe([])->and($this->challenges)->toHaveCount(1);
})->with([
    'drop' => ['DROP TABLE fixture'], 'truncate' => ['TRUNCATE TABLE fixture'],
    'delete' => ['DELETE FROM fixture'], 'multi-table delete' => ['DELETE fixture FROM fixture'],
    'comment and newline' => ["DELETE /* reason */ fixture\nFROM fixture"],
    'executable comment' => ['/*!50000DELETE FROM fixture*/'],
    'update' => ['UPDATE fixture SET value = 0'],
    'narrow column' => ['ALTER TABLE fixture MODIFY COLUMN value VARCHAR(1)'],
    'combined alter' => ['ALTER TABLE fixture ADD COLUMN example INT, DROP COLUMN old'],
    'multiple statements' => ['CREATE TABLE example (id INT); DELETE FROM fixture'],
    'read then delete' => ['SELECT 1; DELETE FROM fixture'],
    'explain analyze' => ['EXPLAIN ANALYZE DELETE fixture FROM fixture'],
]);

test('conditional schema deletion invisible to pretend is stopped before execution', function () {
    $this->operation = function (): void {
        $schema = $this->connection->getSchemaBuilder();
        if ($schema->hasTable('obsolete')) {
            $schema->drop('obsolete');
        }
    };
    expect(fn () => $this->guard->run([], str_repeat('a', 64), str_repeat('b', 40)))
        ->toThrow(RuntimeException::class, 'refusée avant exécution');
    expect($this->executed)->toHaveCount(1)
        ->and(strtolower($this->executed[0]))->toStartWith('select')
        ->and($this->challenges[0][0])->toContain('drop table');
});

test('destructive consent is bound to the actual SQL bindings target and revision', function () {
    $this->response = 'exact';
    $sql = 'DELETE fixture FROM fixture WHERE id = ?';
    $this->operation = fn () => $this->connection->delete($sql, [42]);
    expect($this->guard->run([], str_repeat('a', 64), str_repeat('b', 40)))->toBe(0)
        ->and($this->challenges[0][0])->toContain('42')
        ->and($this->challenges[0][2])->toBe('AUTORISER '.str_repeat('a', 64).' '.str_repeat('b', 40).' '.hash('sha256', json_encode([$sql, [42]])))
        ->and(file_get_contents($this->runtime.'/storage/logs/deployments.log'))->toContain('Autorisation humaine reçue')
        ->and($this->executed)->toBe([$sql]);
});

test('an environment override cannot authorize a destructive migration', function () {
    $previous = getenv('ALLOW_DESTRUCTIVE_MIGRATIONS');
    putenv('ALLOW_DESTRUCTIVE_MIGRATIONS=1');
    $this->operation = fn () => $this->connection->statement('DROP TABLE fixture');
    try {
        expect(fn () => $this->guard->run([], str_repeat('a', 64), str_repeat('b', 40)))->toThrow(RuntimeException::class);
        expect($this->executed)->toBe([]);
    } finally {
        putenv($previous === false ? 'ALLOW_DESTRUCTIVE_MIGRATIONS' : 'ALLOW_DESTRUCTIVE_MIGRATIONS='.$previous);
    }
});

test('destructive confirmation cannot be reused after a change', function (string $change) {
    $sql = 'DROP TABLE fixture';
    $this->response = 'AUTORISER '.str_repeat('a', 64).' '.str_repeat('b', 40).' '.hash('sha256', json_encode([$sql, []]));
    if ($change === 'refusal') {
        $this->response = 'non';
    } elseif ($change === 'no input') {
        $this->response = null;
    } elseif ($change === 'SQL changed') {
        $sql = 'DELETE fixture FROM fixture';
    } else {
        $this->afterConfirmation = function () use ($change): void {
            if (in_array($change, ['revision changed', 'dirty code'], true)) {
                $this->revisionValid = false;
            } elseif ($change === 'maintenance changed') {
                $this->report['maintenance'] = false;
            } else {
                $this->report['fingerprint'] = str_repeat('c', 64);
            }
        };
    }
    $this->operation = fn () => $this->connection->statement($sql);
    expect(fn () => $this->guard->run([], str_repeat('a', 64), str_repeat('b', 40)))->toThrow(RuntimeException::class);
    expect($this->executed)->toBe([]);
})->with(['refusal', 'no input', 'SQL changed', 'revision changed', 'dirty code', 'target changed', 'maintenance changed']);

test('secondary connections cannot execute queries during migrations', function () {
    $secondary = new MySqlConnection($this->createMock(PDO::class), 'other', '', ['name' => 'other']);
    $this->operation = function () use ($secondary): void {
        $this->events->dispatch(new ConnectionEstablished($secondary));
        $secondary->statement('CREATE TABLE unexpected (id INT)');
    };
    expect(fn () => $this->guard->run([], str_repeat('a', 64), str_repeat('b', 40)))
        ->toThrow(RuntimeException::class, 'connexion secondaire');
    expect($this->executed)->toBe([]);
});

test('the protected preview cannot execute SQL on a secondary connection', function () {
    $secondary = new MySqlConnection($this->createMock(PDO::class), 'other', '', ['name' => 'other']);
    $this->operation = function () use ($secondary): void {
        $this->events->dispatch(new ConnectionEstablished($secondary));
        $secondary->statement('DELETE fixture FROM fixture');
    };
    expect(fn () => $this->guard->run([], str_repeat('a', 64), str_repeat('b', 40), true))
        ->toThrow(RuntimeException::class, 'connexion secondaire');
    expect($this->executed)->toBe([]);
});

test('previewed SQL is not executed and grants no permission to actual SQL', function () {
    $this->operation = fn () => $this->connection->statement('DROP TABLE fixture');
    expect($this->guard->run([], str_repeat('a', 64), str_repeat('b', 40), true))->toBe(0)
        ->and($this->executed)->toBe([])->and($this->challenges)->toBe([]);
    $this->connection = new MySqlConnection($this->pdo, 'fixture', '', ['name' => 'mysql', 'database' => 'fixture']);
    expect(fn () => $this->guard->run([], str_repeat('a', 64), str_repeat('b', 40)))
        ->toThrow(RuntimeException::class, 'refusée avant exécution');
    expect($this->executed)->toBe([]);
});
