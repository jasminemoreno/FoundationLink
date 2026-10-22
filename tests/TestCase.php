<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Safety check: tests wipe and refill the database (RefreshDatabase), so they
     * must only ever run on the throwaway in-memory SQLite database from phpunit.xml.
     * If settings are cached (`php artisan config:cache` / `optimize`), phpunit.xml is
     * ignored and tests would hit the real MySQL database — so stop before anything
     * touches it. This runs before any test or database reset.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException(
                "Refusing to run tests: they would use the '{$connection}' database '{$database}' "
                . "instead of the in-memory test database, and wipe its data. "
                . ($app->configurationIsCached()
                    ? 'Settings are cached — run `php artisan config:clear`, then run the tests again.'
                    : 'Check DB_CONNECTION / DB_DATABASE in phpunit.xml and your environment.')
            );
        }

        return $app;
    }
}
