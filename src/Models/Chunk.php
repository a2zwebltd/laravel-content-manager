<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Models;

use A2ZWeb\ContentManager\Concerns\FiresContentEvents;
use A2ZWeb\ContentManager\Database\Factories\ChunkFactory;
use A2ZWeb\ContentManager\Support\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A named snippet of content a template can drop in by code — banner copy, a
 * disclaimer, a CTA line — editable without a deploy.
 */
class Chunk extends Model
{
    use FiresContentEvents;

    /** @use HasFactory<ChunkFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'content',
        'is_published',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Without this the cached value outlives every edit by up to the TTL —
        // an hour of "my change didn't apply", and worse once chunks became
        // editable remotely over MCP.
        static::saved(fn (Chunk $chunk) => $chunk->forgetCache());
        static::deleted(fn (Chunk $chunk) => $chunk->forgetCache());
    }

    public function getTable(): string
    {
        return Tables::chunks();
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /** @param Builder<Chunk> $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public static function getByCode(string $code): ?string
    {
        $ttl = (int) config('content-manager.chunks.cache_ttl', 3600);

        $lookup = static fn (): ?string => static::query()
            ->published()
            ->where('code', $code)
            ->value('content');

        return $ttl > 0
            ? Cache::remember(static::cacheKey($code), $ttl, $lookup)
            : $lookup();
    }

    public function forgetCache(): void
    {
        Cache::forget(static::cacheKey((string) $this->code));

        if ($this->isDirty('code') && is_string($original = $this->getOriginal('code'))) {
            Cache::forget(static::cacheKey($original));
        }
    }

    public static function cacheKey(string $code): string
    {
        return 'content-manager.chunk.'.$code;
    }

    protected static function newFactory(): Factory
    {
        return ChunkFactory::new();
    }
}
