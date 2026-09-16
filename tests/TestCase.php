<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Tests;

use A2ZWeb\ContentManager\ContentManagerServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Feed\FeedServiceProvider;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [
            MediaLibraryServiceProvider::class,
            FeedServiceProvider::class,
            McpServiceProvider::class,
            ContentManagerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => sys_get_temp_dir().'/content-manager-test',
            'url' => '/storage',
            'visibility' => 'public',
        ]);

        // A key is what brings the MCP endpoint into existence, so tests that
        // exercise it need one configured before the provider boots.
        $app['config']->set('content-manager.mcp.keys', [
            ['name' => 'writer', 'key' => 'test-write-key', 'abilities' => ['read', 'write']],
            ['name' => 'reader', 'key' => 'test-read-key', 'abilities' => ['read']],
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
