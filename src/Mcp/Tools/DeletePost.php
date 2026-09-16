<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Tools;

use A2ZWeb\ContentManager\Mcp\Concerns\AuthorizesAbilities;
use A2ZWeb\ContentManager\Mcp\Concerns\ResolvesRecords;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('delete-post')]
#[Description('Soft-delete a blog post so it can be restored later. Pass force to remove it permanently — that cannot be undone.')]
class DeletePost extends Tool
{
    use AuthorizesAbilities;
    use ResolvesRecords;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($this->cannotWrite()) {
            return Response::error($this->readOnlyMessage());
        }

        $validated = $request->validate([
            'slug' => 'string|max:255',
            'id' => 'integer',
            'force' => 'boolean',
        ]);

        $post = $this->findPost($validated);

        if ($post === null) {
            return Response::error($this->postNotFoundMessage($validated));
        }

        $force = (bool) ($validated['force'] ?? false);
        $slug = (string) $post->slug;

        $force ? $post->forceDelete() : $post->delete();

        $this->recordMutation($force ? 'post force-deleted' : 'post deleted', ['slug' => $slug]);

        return Response::structured([
            'deleted' => true,
            'slug' => $slug,
            'permanent' => $force,
            'note' => $force
                ? 'The post is gone for good.'
                : 'Soft-deleted. The slug stays reserved until the post is restored or force-deleted.',
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Slug of the post. Either slug or id is required.'),
            'id' => $schema->integer()->description('Id of the post.'),
            'force' => $schema->boolean()->description('Permanently delete instead of soft-deleting.')->default(false),
        ];
    }
}
