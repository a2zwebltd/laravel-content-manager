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

#[Name('list-categories')]
#[Description('List the blog categories with how many published posts each one holds.')]
class ListCategories extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $categories = Models::contentCategory()::query()
            ->withCount(['blogPosts' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get()
            ->map(fn ($category): array => [
                'id' => $category->id,
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
                'published_posts' => (int) $category->blog_posts_count,
            ])
            ->all();

        return Response::structured(['categories' => $categories]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
