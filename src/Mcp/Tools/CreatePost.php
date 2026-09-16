<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Tools;

use A2ZWeb\ContentManager\Mcp\Concerns\AuthorizesAbilities;
use A2ZWeb\ContentManager\Mcp\Concerns\PresentsContent;
use A2ZWeb\ContentManager\Mcp\Concerns\ResolvesRecords;
use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('create-post')]
#[Description('Create a blog post. Leave published_at empty to file it as a draft for a human to review. Read content://guidelines first.')]
class CreatePost extends Tool
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
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'slug' => 'string|max:255',
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
        ], [
            'title.required' => 'A post needs a title.',
            'content.required' => 'A post needs a markdown body in "content".',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['title']);

        // A soft-deleted post keeps its slug reserved by the unique index, so
        // say that plainly instead of letting the insert blow up.
        if ($existing = $this->findPost(['slug' => $slug])) {
            return Response::error(sprintf(
                'The slug "%s" is already taken by post #%d (%s). Pass a different slug, or use update-post.',
                $slug,
                $existing->id,
                $existing->trashed() ? 'soft-deleted' : 'live',
            ));
        }

        $post = Models::blogPost()::query()->create([
            'title' => $validated['title'],
            'slug' => $slug,
            'content' => $validated['content'],
            'subtitle' => $validated['subtitle'] ?? null,
            'intro' => $validated['intro'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'meta_keywords' => $validated['meta_keywords'] ?? null,
            'youtube_embed' => $validated['youtube_embed'] ?? null,
            'is_promoted' => $validated['is_promoted'] ?? false,
            'published_at' => $validated['published_at'] ?? null,
        ]);

        $missing = $this->syncTaxonomy($post, $validated['category_slugs'] ?? null, $validated['tag_slugs'] ?? null);

        $this->recordMutation('post created', ['slug' => $post->slug]);

        $post->load(['categories', 'tags']);

        return Response::structured(array_filter([
            'post' => $this->presentPost($post),
            'unknown_categories' => $missing === [] ? null : $missing,
        ], static fn ($value) => $value !== null));
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Post title.')->required(),
            'content' => $schema->string()->description('The body as markdown. Do not repeat the title as an H1.')->required(),
            'slug' => $schema->string()->description('URL slug. Derived from the title when omitted.'),
            'subtitle' => $schema->string()->description('One-line subtitle.'),
            'intro' => $schema->string()->description('Short standfirst shown in listings.'),
            'meta_title' => $schema->string()->description('SERP title tag.'),
            'meta_description' => $schema->string()->description('SERP meta description.'),
            'meta_keywords' => $schema->string()->description('Comma-separated keywords.'),
            'youtube_embed' => $schema->string()->description('YouTube embed id or URL.'),
            'is_promoted' => $schema->boolean()->description('Feature the post.')->default(false),
            'published_at' => $schema->string()->description('ISO date to publish at. Omit to leave it a draft.'),
            'category_slugs' => $schema->array()->items($schema->string())->description('Existing category slugs — see content://taxonomy.'),
            'tag_slugs' => $schema->array()->items($schema->string())->description('Tag slugs. Tags that do not exist yet are created.'),
        ];
    }
}
