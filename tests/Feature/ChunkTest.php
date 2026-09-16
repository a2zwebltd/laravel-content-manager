<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\Models\Chunk;
use Illuminate\Support\Facades\Cache;

it('returns published chunk content by code', function (): void {
    Chunk::factory()->create(['code' => 'cta', 'content' => 'Start your trial']);

    expect(Chunk::getByCode('cta'))->toBe('Start your trial');
});

it('ignores unpublished chunks', function (): void {
    Chunk::factory()->unpublished()->create(['code' => 'hidden', 'content' => 'nope']);

    expect(Chunk::getByCode('hidden'))->toBeNull();
});

it('forgets the cached value when the chunk changes', function (): void {
    $chunk = Chunk::factory()->create(['code' => 'banner', 'content' => 'Old copy']);

    expect(Chunk::getByCode('banner'))->toBe('Old copy');
    expect(Cache::has(Chunk::cacheKey('banner')))->toBeTrue();

    $chunk->update(['content' => 'New copy']);

    expect(Chunk::getByCode('banner'))->toBe('New copy');
});

it('forgets the cached value under the old code when the code is renamed', function (): void {
    $chunk = Chunk::factory()->create(['code' => 'old-code', 'content' => 'Copy']);

    Chunk::getByCode('old-code');

    $chunk->update(['code' => 'new-code']);

    expect(Cache::has(Chunk::cacheKey('old-code')))->toBeFalse();
    expect(Chunk::getByCode('new-code'))->toBe('Copy');
});

it('skips caching entirely when the ttl is zero', function (): void {
    config()->set('content-manager.chunks.cache_ttl', 0);

    Chunk::factory()->create(['code' => 'live', 'content' => 'First']);

    expect(Chunk::getByCode('live'))->toBe('First');
    expect(Cache::has(Chunk::cacheKey('live')))->toBeFalse();
});
