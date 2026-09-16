<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Tools;

use A2ZWeb\ContentManager\Mcp\Concerns\PresentsContent;
use A2ZWeb\ContentManager\Mcp\Concerns\ResolvesRecords;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get-post')]
#[Description('Fetch one blog post by slug or id, including its full markdown body, categories and tags.')]
class GetPost extends Tool
{
    use PresentsContent;
    use ResolvesRecords;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'slug' => 'string|max:255',
            'id' => 'integer',
            'with_content' => 'boolean',
        ]);

        $post = $this->findPost($validated);

        if ($post === null) {
            return Response::error($this->postNotFoundMessage($validated));
        }

        $post->load(['categories', 'tags', 'media']);

        return Response::structured([
            'post' => $this->presentPost($post, $validated['with_content'] ?? true),
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('The post slug. Either slug or id is required.'),
            'id' => $schema->integer()->description('The post id. Either slug or id is required.'),
            'with_content' => $schema->boolean()->description('Include the full markdown body.')->default(true),
        ];
    }
}
