<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\ContentManagerServiceProvider as Provider;
use Illuminate\Support\Facades\Route;

it('registers the MCP endpoint when a key is configured', function (): void {
    $uris = collect(Route::getRoutes()->getRoutes())->map(fn ($route) => $route->uri())->all();

    expect($uris)->toContain('mcp/content')
        ->and(Provider::mcpIsAvailable())->toBeTrue();
});

it('will not register the endpoint when no key is configured', function (): void {
    config()->set('content-manager.mcp.keys', []);

    expect(Provider::mcpIsAvailable())->toBeFalse();
});

it('will not register the endpoint when MCP is switched off', function (): void {
    config()->set('content-manager.mcp.enabled', false);

    expect(Provider::mcpIsAvailable())->toBeFalse();
});

it('serves the endpoint on the configured path', function (): void {
    expect(config('content-manager.mcp.path'))->toBe('mcp/content');
});
