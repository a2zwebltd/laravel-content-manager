<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\Models\Page;

it('serves a published page from the whitelist', function (): void {
    Page::factory()->published()->create(['slug' => 'about', 'title' => 'About us']);

    $this->get('/about')
        ->assertOk()
        ->assertSee('About us');
});

it('404s on an unpublished page', function (): void {
    Page::factory()->create(['slug' => 'privacy', 'published_at' => null]);

    $this->get('/privacy')->assertNotFound();
});

it('404s on a page published in the future', function (): void {
    Page::factory()->create(['slug' => 'terms', 'published_at' => now()->addDay()]);

    $this->get('/terms')->assertNotFound();
});

it('does not route slugs outside the whitelist', function (): void {
    Page::factory()->published()->create(['slug' => 'secret-plans']);

    $this->get('/secret-plans')->assertNotFound();
});

it('scopes route binding to published pages', function (): void {
    $page = Page::factory()->create(['slug' => 'about', 'published_at' => null]);

    expect((new Page)->resolveRouteBinding('about'))->toBeNull();

    $page->update(['published_at' => now()->subDay()]);

    expect((new Page)->resolveRouteBinding('about')?->id)->toBe($page->id);
});
