<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Tools;

use A2ZWeb\ContentManager\Mcp\Concerns\AuthorizesAbilities;
use A2ZWeb\ContentManager\Mcp\Concerns\PresentsContent;
use A2ZWeb\ContentManager\Mcp\Concerns\ResolvesRecords;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('update-post')]
#[Description('Update an existing blog post. Only the fields you pass are changed; everything else is left alone.')]
class UpdatePost extends Tool
{
    use AuthorizesAbilities;
    use PresentsContent;
    use ResolvesRecords;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($this->cannotWrite()) {
            return Response::error($this->readOnlyMessage());
        }

        $validated = $request->validate([
            'slug' => 'string|max:255',
            'id' => 'integer',
            'title' => 'string|max:255',
            'new_slug' => 'string|max:255',
            'content' => 'string',
            'subtitle' => 'string|max:255',
            'intro' => 'string|max:1000',
            'meta_title' => 'string|max:255',
            'meta_description' => 'string|max:1000',
            'meta_keywords' => 'string|max:255',
            'youtube_embed' => 'string|max:255',
            'is_promoted' => 'boolean',
            'published_at' => 'date',
            'category_slugs' => 'array',
            'category_slugs.*' => 'string',
            'tag_slugs' => 'array',
            'tag_slugs.*' => 'string',
        ]);

        $post = $this->findPost($validated);

        if ($post === null) {
            return Response::error($this->postNotFoundMessage($validated));
        }

        $changes = array_filter([
            'title' => $validated['title'] ?? null,
            'slug' => $validated['new_slug'] ?? null,
            'content' => $validated['content'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'intro' => $validated['intro'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'meta_keywords' => $validated['meta_keywords'] ?? null,
            'youtube_embed' => $validated['youtube_embed'] ?? null,
            'published_at' => $validated['published_at'] ?? null,
        ], static fn ($value) => $value !== null);

        if (array_key_exists('is_promoted', $validated)) {
            $changes['is_promoted'] = $validated['is_promoted'];
        }

        // Pivots first, save last: a column change then announces the edit
        // once, with the new taxonomy already in place, and a taxonomy-only
        // change is announced by syncTaxonomy() touching the post.
        $post->fill($changes);

        $missing = $this->syncTaxonomy($post, $validated['category_slugs'] ?? null, $validated['tag_slugs'] ?? null);

        $post->save();

        $this->recordMutation('post updated', ['slug' => $post->slug, 'fields' => array_keys($changes)]);

        $post->load(['categories', 'tags']);

        return Response::structured(array_filter([
            'post' => $this->presentPost($post),
            'updated_fields' => array_keys($changes),
            'unknown_categories' => $missing === [] ? null : $missing,
        ], static fn ($value) => $value !== null));
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Slug of the post to update. Either slug or id is required.'),
            'id' => $schema->integer()->description('Id of the post to update.'),
            'title' => $schema->string()->description('New title.'),
            'new_slug' => $schema->string()->description('Change the slug. This changes the post URL — old links break.'),
            'content' => $schema->string()->description('Replacement markdown body.'),
            'subtitle' => $schema->string(),
            'intro' => $schema->string(),
            'meta_title' => $schema->string(),
            'meta_description' => $schema->string(),
            'meta_keywords' => $schema->string(),
            'youtube_embed' => $schema->string(),
            'is_promoted' => $schema->boolean(),
            'published_at' => $schema->string()->description('ISO date. Use publish-post / unpublish-post for the common cases.'),
            'category_slugs' => $schema->array()->items($schema->string())->description('Replaces the current categories.'),
            'tag_slugs' => $schema->array()->items($schema->string())->description('Replaces the current tags.'),
        ];
    }
}
