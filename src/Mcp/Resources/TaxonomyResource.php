<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Resources;

use A2ZWeb\ContentManager\Support\Models;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('taxonomy')]
#[Uri('content://taxonomy')]
#[MimeType('application/json')]
#[Description('Every category and tag slug that already exists, with post counts — so a new post is filed under the existing scheme instead of near-duplicates.')]
class TaxonomyResource extends Resource
{
    public function handle(Request $request): Response
    {
        $categories = Models::contentCategory()::query()
            ->withCount(['blogPosts' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get()
            ->map(fn ($category): array => [
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
                'published_posts' => (int) $category->blog_posts_count,
            ])
            ->all();

        $tags = Models::tag()::query()
            ->withCount(['blogPosts' => fn ($q) => $q->published()])
            ->orderByDesc('blog_posts_count')
            ->get()
            ->map(fn ($tag): array => [
                'name' => (string) $tag->name,
                'slug' => (string) $tag->slug,
                'published_posts' => (int) $tag->blog_posts_count,
            ])
            ->all();

        return Response::json([
            'categories' => $categories,
            'tags' => $tags,
            'note' => 'Categories must already exist to be attached to a post; missing tags are created automatically.',
        ]);
    }
}
