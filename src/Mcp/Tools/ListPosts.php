<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Tools;

use A2ZWeb\ContentManager\Mcp\Concerns\PresentsContent;
use A2ZWeb\ContentManager\Support\Models;
use A2ZWeb\ContentManager\Support\Tables;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('list-posts')]
#[Description('List blog posts, newest first. Filter by publication status, category slug, tag slug or a free-text search across title, intro and body.')]
class ListPosts extends Tool
{
    use PresentsContent;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'status' => 'in:any,published,draft,trashed',
            'search' => 'string|max:200',
            'category' => 'string|max:255',
            'tag' => 'string|max:255',
            'per_page' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
        ]);

        $status = $validated['status'] ?? 'any';
        $perPage = (int) ($validated['per_page'] ?? 25);

        $query = Models::blogPost()::query()->with(['categories', 'tags', 'media']);

        match ($status) {
            'published' => $query->published(),
            'draft' => $query->whereNull('published_at'),
            'trashed' => $query->onlyTrashed(),
            default => $query->withTrashed(),
        };

        if ($search = $validated['search'] ?? null) {
            $escaped = '%'.addcslashes($search, '%_\\').'%';

            $query->where(fn ($q) => $q
                ->where('title', 'like', $escaped)
                ->orWhere('intro', 'like', $escaped)
                ->orWhere('content', 'like', $escaped));
        }

        if ($category = $validated['category'] ?? null) {
            $query->whereHas('categories', fn ($q) => $q->where(Tables::contentCategories().'.slug', $category));
        }

        if ($tag = $validated['tag'] ?? null) {
            $query->whereHas('tags', fn ($q) => $q->where(Tables::tags().'.slug', $tag));
        }

        $posts = $query->latest('published_at')
            ->paginate($perPage, ['*'], 'page', (int) ($validated['page'] ?? 1));

        return Response::structured([
            'total' => $posts->total(),
            'page' => $posts->currentPage(),
            'last_page' => $posts->lastPage(),
            'posts' => array_map(fn ($post) => $this->presentPost($post), $posts->items()),
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(['any', 'published', 'draft', 'trashed'])
                ->description('Which posts to return. Defaults to any.')
                ->default('any'),
            'search' => $schema->string()->description('Free-text search across title, intro and body.'),
            'category' => $schema->string()->description('Category slug to filter by.'),
            'tag' => $schema->string()->description('Tag slug to filter by.'),
            'per_page' => $schema->integer()->description('Results per page, 1-100.')->default(25),
            'page' => $schema->integer()->description('Page number, starting at 1.')->default(1),
        ];
    }
}
