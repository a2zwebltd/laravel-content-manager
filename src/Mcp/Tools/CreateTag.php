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

#[Name('create-tag')]
#[Description('Create a tag. Attaching tags to a post with create-post or update-post already creates missing ones, so use this only to set a tag up with its own description and meta.')]
class CreateTag extends Tool
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
            'type' => 'string|max:255',
            'content' => 'string',
            'meta_title' => 'string|max:255',
            'meta_description' => 'string|max:1000',
            'meta_keywords' => 'string|max:255',
        ]);

        $tag = Models::tag()::query()->firstOrCreate(
            ['slug' => $validated['slug'] ?? Str::slug($validated['name'])],
            [
                'name' => $validated['name'],
                'type' => $validated['type'] ?? 'blog',
                'content' => $validated['content'] ?? null,
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_description' => $validated['meta_description'] ?? null,
                'meta_keywords' => $validated['meta_keywords'] ?? null,
            ],
        );

        $this->recordMutation('tag created', ['slug' => $tag->slug]);

        return Response::structured([
            'tag' => [
                'id' => $tag->id,
                'name' => (string) $tag->name,
                'slug' => (string) $tag->slug,
                'type' => $tag->type,
            ],
            'created' => $tag->wasRecentlyCreated,
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Display name.')->required(),
            'slug' => $schema->string()->description('URL slug. Derived from the name when omitted.'),
            'type' => $schema->string()->description('Tag type. Defaults to "blog".'),
            'content' => $schema->string()->description('Description shown on the tag page.'),
            'meta_title' => $schema->string(),
            'meta_description' => $schema->string(),
            'meta_keywords' => $schema->string(),
        ];
    }
}
