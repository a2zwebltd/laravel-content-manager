<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Support;

/**
 * Flat listings for llms.txt-style discovery files: title, URL, description
 * and — when asked — the raw markdown body.
 */
class ContentIndex
{
    /** @return array<int, array<string, mixed>> */
    public static function posts(bool $withContent = false, ?int $limit = null): array
    {
        $model = Models::blogPost();

        $query = $model::query()
            ->published()
            ->latest('published_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(function ($post) use ($withContent): array {
                return array_filter([
                    'title' => (string) $post->title,
                    'url' => Routes::url('show', $post->slug),
                    'description' => (string) ($post->intro ?? $post->subtitle ?? ''),
                    'published_at' => $post->published_at,
                    'content' => $withContent ? (string) $post->content : null,
                ], static fn ($value) => $value !== null);
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public static function pages(bool $withContent = false): array
    {
        $model = Models::page();

        return $model::query()
            ->published()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($page) use ($withContent): array {
                return array_filter([
                    'title' => (string) $page->title,
                    'url' => Routes::url('page', $page->slug),
                    'description' => (string) ($page->meta_description ?? ''),
                    'content' => $withContent ? (string) $page->content : null,
                ], static fn ($value) => $value !== null);
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public static function faqs(?string $group = null): array
    {
        $model = Models::faq();

        $query = $model::query()->published()->orderBy('sort_order');

        if ($group !== null) {
            $query->group($group);
        }

        return $query->get()
            ->map(fn ($faq): array => [
                'question' => (string) $faq->question,
                'answer' => (string) $faq->answer,
            ])
            ->all();
    }
}
