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

#[Name('list-tags')]
#[Description('List tags with how many published posts each one holds.')]
class ListTags extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'type' => 'string|max:255',
            'search' => 'string|max:200',
        ]);

        $query = Models::tag()::query()
            ->withCount(['blogPosts' => fn ($q) => $q->published()])
            ->orderBy('name');

        if ($type = $validated['type'] ?? null) {
            $query->where('type', $type);
        }

        if ($search = $validated['search'] ?? null) {
            $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
        }

        return Response::structured([
            'tags' => $query->get()->map(fn ($tag): array => [
                'id' => $tag->id,
                'name' => (string) $tag->name,
                'slug' => (string) $tag->slug,
                'type' => $tag->type,
                'published_posts' => (int) $tag->blog_posts_count,
            ])->all(),
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('Filter by tag type, e.g. "blog".'),
            'search' => $schema->string()->description('Filter by name.'),
        ];
    }
}
