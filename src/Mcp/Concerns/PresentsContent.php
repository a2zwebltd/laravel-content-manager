<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Concerns;

use A2ZWeb\ContentManager\Support\Markdown;
use A2ZWeb\ContentManager\Support\Routes;
use Illuminate\Database\Eloquent\Model;

trait PresentsContent
{
    /** @return array<string, mixed> */
    protected function presentPost(Model $post, bool $withContent = false): array
    {
        return array_filter([
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'subtitle' => $post->subtitle,
            'intro' => $post->intro,
            'status' => $post->isPublished() ? 'published' : ($post->trashed() ? 'trashed' : 'draft'),
            'published_at' => $post->published_at?->toIso8601String(),
            'is_promoted' => (bool) $post->is_promoted,
            'url' => Routes::url('show', $post->slug),
            'categories' => $post->relationLoaded('categories')
                ? $post->categories->pluck('slug')->all()
                : null,
            'tags' => $post->relationLoaded('tags')
                ? $post->tags->pluck('slug')->all()
                : null,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'meta_keywords' => $post->meta_keywords,
            'youtube_embed' => $post->youtube_embed,
            'word_count' => Markdown::wordCount((string) $post->content),
            'reading_minutes' => $post->readingTime(),
            'image_url' => ($url = $post->getFirstMediaUrl('main')) !== '' ? $url : null,
            'content' => $withContent ? (string) $post->content : null,
        ], static fn ($value) => $value !== null);
    }

    /** @return array<string, mixed> */
    protected function presentPage(Model $page, bool $withContent = false): array
    {
        return array_filter([
            'id' => $page->id,
            'slug' => $page->slug,
            'title' => $page->title,
            'meta_description' => $page->meta_description,
            'sort_order' => $page->sort_order,
            'status' => $page->isPublished() ? 'published' : 'draft',
            'published_at' => $page->published_at?->toIso8601String(),
            'url' => Routes::url('page', $page->slug),
            'content' => $withContent ? (string) $page->content : null,
        ], static fn ($value) => $value !== null);
    }

    /** @return array<string, mixed> */
    protected function presentFaq(Model $faq): array
    {
        return array_filter([
            'id' => $faq->id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'group' => $faq->group,
            'sort_order' => $faq->sort_order,
            'status' => $faq->published_at !== null && $faq->published_at->isPast() ? 'published' : 'draft',
            'published_at' => $faq->published_at?->toIso8601String(),
        ], static fn ($value) => $value !== null);
    }
}
