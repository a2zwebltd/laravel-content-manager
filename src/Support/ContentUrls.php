<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Support;

use Illuminate\Support\Collection;

/**
 * Public content as plain arrays a host can hand to whatever sitemap builder
 * it uses. Deliberately not spatie/laravel-sitemap Url objects — the package
 * has no business requiring a sitemap package to describe its own URLs.
 *
 * Each row: ['loc' => string, 'lastmod' => ?Carbon, 'changefreq' => string,
 *            'priority' => float, 'image' => ?['url' => string, 'caption' => string]]
 */
class ContentUrls
{
    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return array_merge(self::posts(), self::categories(), self::tags(), self::pages());
    }

    /** @return array<int, array<string, mixed>> */
    public static function posts(): array
    {
        $model = Models::blogPost();

        return $model::query()
            ->published()
            ->with('media')
            ->latest('published_at')
            ->get()
            ->map(function ($post): ?array {
                $loc = Routes::url('show', $post->slug);

                if ($loc === null) {
                    return null;
                }

                $image = method_exists($post, 'getFirstMediaUrl')
                    ? $post->getFirstMediaUrl('main', 'full_size')
                    : '';

                return [
                    'loc' => $loc,
                    'lastmod' => $post->updated_at ?? $post->published_at,
                    'changefreq' => 'monthly',
                    'priority' => 0.8,
                    'image' => $image !== '' ? ['url' => $image, 'caption' => (string) $post->title] : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public static function categories(): array
    {
        return self::taxonomy(Models::contentCategory(), 'category');
    }

    /** @return array<int, array<string, mixed>> */
    public static function tags(): array
    {
        return self::taxonomy(Models::tag(), 'tag');
    }

    /** @return array<int, array<string, mixed>> */
    public static function pages(): array
    {
        $model = Models::page();

        return $model::query()
            ->published()
            ->get()
            ->map(function ($page): ?array {
                $loc = Routes::url('page', $page->slug);

                return $loc === null ? null : [
                    'loc' => $loc,
                    'lastmod' => $page->updated_at,
                    'changefreq' => 'yearly',
                    'priority' => 0.4,
                    'image' => null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  class-string  $model
     * @return array<int, array<string, mixed>>
     */
    private static function taxonomy(string $model, string $routeKey): array
    {
        return $model::query()
            ->whereHas('blogPosts', fn ($query) => $query->published())
            ->get()
            ->map(function ($term) use ($routeKey): ?array {
                $loc = Routes::url($routeKey, $term->slug);

                return $loc === null ? null : [
                    'loc' => $loc,
                    'lastmod' => $term->updated_at,
                    'changefreq' => 'weekly',
                    'priority' => 0.5,
                    'image' => null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /** @return Collection<int, array<string, mixed>> */
    public static function collect(): Collection
    {
        return new Collection(self::all());
    }
}
