<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PDO;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = $app->make('db')->connection();

        if ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() !== ':memory:') {
            throw new RuntimeException('Tests refused: explicitly configure SQLite :memory: before initializing any schema.');
        }

        if ($connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            throw new RuntimeException('Tests refused: the observed PDO driver is not SQLite.');
        }

        foreach ($connection->select('PRAGMA database_list') as $database) {
            if ($database->file !== '') {
                throw new RuntimeException('Tests refused: a persistent database is attached.');
            }
        }

        return $app;
    }
}
