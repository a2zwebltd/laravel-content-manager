<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Tools;

use A2ZWeb\ContentManager\Mcp\Concerns\AuthorizesAbilities;
use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('create-category')]
#[Description('Create a blog category. Categories are an editorial decision — check list-categories first rather than inventing near-duplicates.')]
class CreateCategory extends Tool
{
    use AuthorizesAbilities;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($this->cannotWrite()) {
            return Response::error($this->readOnlyMessage());
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'string|max:255',
            'content' => 'string',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        $category = Models::contentCategory()::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $validated['name'], 'content' => $validated['content'] ?? null],
        );

        $this->recordMutation('category created', ['slug' => $category->slug]);

        return Response::structured([
            'category' => [
                'id' => $category->id,
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
            ],
            'created' => $category->wasRecentlyCreated,
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Display name.')->required(),
            'slug' => $schema->string()->description('URL slug. Derived from the name when omitted.'),
            'content' => $schema->string()->description('Optional description shown on the category page.'),
        ];
    }
}
