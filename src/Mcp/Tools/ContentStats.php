<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Tools;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('content-stats')]
#[Description('A count of what this site holds: published and draft posts, categories, tags, pages, FAQ entries and chunks, plus the most recent post.')]
class ContentStats extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $posts = Models::blogPost()::query();
        $latest = Models::blogPost()::query()->published()->latest('published_at')->first();

        return Response::structured([
            'posts' => [
                'published' => (clone $posts)->published()->count(),
                'drafts' => (clone $posts)->whereNull('published_at')->count(),
                'trashed' => (clone $posts)->onlyTrashed()->count(),
            ],
            'categories' => Models::contentCategory()::query()->count(),
            'tags' => Models::tag()::query()->count(),
            'pages' => Models::page()::query()->count(),
            'faqs' => Models::faq()::query()->count(),
            'chunks' => Models::chunk()::query()->count(),
            'latest_post' => $latest === null ? null : [
                'title' => (string) $latest->title,
                'slug' => (string) $latest->slug,
                'published_at' => $latest->published_at?->toIso8601String(),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
