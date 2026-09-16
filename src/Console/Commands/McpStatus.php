<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Server as McpServer;

/**
 * Answers "why is my MCP endpoint 404ing" without a debugging session.
 */
class McpStatus extends Command
{
    protected $signature = 'content:mcp-status';

    protected $description = 'Show whether the content MCP endpoint is registered, and what each API key may do.';

    public function handle(): int
    {
        $path = (string) config('content-manager.mcp.path', 'mcp/content');
        $keys = (array) config('content-manager.mcp.keys', []);

        if (! class_exists(McpServer::class)) {
            $this->error('laravel/mcp is not installed.');

            return self::FAILURE;
        }

        if (! config('content-manager.mcp.enabled', true)) {
            $this->warn('Disabled: content-manager.mcp.enabled is false.');

            return self::SUCCESS;
        }

        if ($keys === []) {
            $this->warn('No API key configured, so the endpoint is not registered at all.');
            $this->line('Set CONTENT_MCP_API_KEY (or CONTENT_MCP_API_KEYS) and clear the config cache.');

            return self::SUCCESS;
        }

        $registered = collect(Route::getRoutes()->getRoutes())
            ->contains(fn ($route) => trim($route->uri(), '/') === trim($path, '/'));

        $this->info('Endpoint: '.url($path).($registered ? '  [registered]' : '  [NOT registered]'));
        $this->newLine();

        $this->table(
            ['Key name', 'Abilities', 'Key'],
            array_map(static fn (array $key): array => [
                $key['name'] ?? 'unknown',
                implode(', ', (array) ($key['abilities'] ?? [])),
                str_repeat('•', 8).substr((string) ($key['key'] ?? ''), -4),
            ], $keys),
        );

        return self::SUCCESS;
    }
}
