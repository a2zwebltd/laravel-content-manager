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

#[Name('unpublish-post')]
#[Description('Take a published post back to draft. The post and its URL stay in the database; it simply stops being served.')]
class UnpublishPost extends Tool
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
        ]);

        $post = $this->findPost($validated, withTrashed: false);

        if ($post === null) {
            return Response::error($this->postNotFoundMessage($validated));
        }

        $post->forceFill(['published_at' => null])->save();

        $this->recordMutation('post unpublished', ['slug' => $post->slug]);

        return Response::structured(['post' => $this->presentPost($post)]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Slug of the post. Either slug or id is required.'),
            'id' => $schema->integer()->description('Id of the post.'),
        ];
    }
}
