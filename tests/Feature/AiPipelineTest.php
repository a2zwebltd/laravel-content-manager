<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\Support\Editorial;
use Illuminate\Support\Facades\Artisan;
use Laravel\Ai\Contracts\Agent;

it('keeps the draft command out of the way when the AI SDK is not installed', function (): void {
    // laravel/ai is a suggestion, not a dependency: the package has to install
    // and boot cleanly without it.
    expect(interface_exists(Agent::class))->toBeFalse()
        ->and(array_key_exists('content:generate-drafts', Artisan::all()))->toBeFalse();
});

it('always registers the MCP status command', function (): void {
    expect(array_key_exists('content:mcp-status', Artisan::all()))->toBeTrue();
});

it('builds editorial guidelines from config', function (): void {
    config()->set('content-manager.editorial', [
        'brand' => 'Acme',
        'description' => 'Acme sells anvils.',
        'audience' => 'Coyotes.',
        'spelling' => 'American',
        'rules' => ['Never fabricate statistics.'],
        'limits' => ['title' => 65],
    ]);

    $guidelines = Editorial::guidelines();

    expect($guidelines)->toContain('Editorial guidelines for Acme')
        ->toContain('Acme sells anvils.')
        ->toContain('Coyotes.')
        ->toContain('Never fabricate statistics.')
        ->toContain('title: 65');
});
