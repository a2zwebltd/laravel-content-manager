<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Tools;

use A2ZWeb\ContentManager\Mcp\Concerns\AuthorizesAbilities;
use A2ZWeb\ContentManager\Mcp\Concerns\PresentsContent;
use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('upsert-page')]
#[Description('Create or update a CMS page, addressed by slug. Note that legal pages usually need a human to approve the wording.')]
class UpsertPage extends Tool
{
    use AuthorizesAbilities;
    use PresentsContent;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($this->cannotWrite()) {
            return Response::error($this->readOnlyMessage());
        }

        $validated = $request->validate([
            'slug' => 'required|string|max:255',
            'title' => 'string|max:255',
            'content' => 'string',
            'meta_description' => 'string|max:255',
            'sort_order' => 'integer|min:0',
            'published_at' => 'date',
            'publish' => 'boolean',
        ]);

        $page = Models::page()::query()->firstOrNew(['slug' => $validated['slug']]);
        $isNew = ! $page->exists;

        if ($isNew && (empty($validated['title']) || empty($validated['content']))) {
            return Response::error('A new page needs both a title and a markdown body in "content".');
        }

        $page->fill(array_filter([
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? null,
            'published_at' => $validated['published_at'] ?? null,
        ], static fn ($value) => $value !== null));

        if (array_key_exists('publish', $validated)) {
            $page->published_at = $validated['publish'] ? ($page->published_at ?? now()) : null;
        }

        $page->save();

        $this->recordMutation($isNew ? 'page created' : 'page updated', ['slug' => $page->slug]);

        return Response::structured([
            'page' => $this->presentPage($page),
            'created' => $isNew,
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Page slug — identifies the page and forms its URL.')->required(),
            'title' => $schema->string()->description('Page title. Required when creating.'),
            'content' => $schema->string()->description('Markdown body. Required when creating.'),
            'meta_description' => $schema->string()->description('SERP meta description.'),
            'sort_order' => $schema->integer()->description('Order in navigation listings.'),
            'published_at' => $schema->string()->description('ISO date to publish at.'),
            'publish' => $schema->boolean()->description('True publishes now, false returns the page to draft.'),
        ];
    }
}
