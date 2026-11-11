<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->ensureTestsUseIsolatedDatabase($app);

        return $app;
    }

    /**
     * Abort unless tests run against a throwaway in-memory SQLite database.
     *
     * RefreshDatabase runs migrate:fresh, which would wipe a real database.
     */
    private function ensureTestsUseIsolatedDatabase(Application $app): void
    {
        $default = $app['config']->get('database.default');
        $driver = $app['config']->get("database.connections.{$default}.driver");
        $database = $app['config']->get("database.connections.{$default}.database");

        if ($driver !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException(sprintf(
                'Refusing to run tests: expected the in-memory SQLite database, got connection "%s" (driver "%s", database "%s"). '
                .'Check DB_CONNECTION/DB_DATABASE in phpunit.xml.',
                $default,
                $driver,
                (string) $database
            ));
        }
    }
}
