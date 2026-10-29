<?php

declare(strict_types=1);

namespace WeBRTeu\Emag\Tests;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Sleep;
use Orchestra\Testbench\TestCase as Orchestra;
use WeBRTeu\Emag\EmagServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [EmagServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('emag.username', 'seller@example.com');
        $app['config']->set('emag.password', 'secret-pass');
    }

    protected function defineDatabaseMigrations(): void
    {
        /** @var Migration $migration */
        $migration = require __DIR__.'/../database/migrations/create_emag_tables.php.stub';
        $migration->up();
    }
}
