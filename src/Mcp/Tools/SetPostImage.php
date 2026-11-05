<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Tools;

use A2ZWeb\ContentManager\Mcp\Concerns\AuthorizesAbilities;
use A2ZWeb\ContentManager\Mcp\Concerns\ResolvesRecords;
use A2ZWeb\ContentManager\Support\RemoteImage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Name('set-post-image')]
#[Description("Set a post's featured image (JPEG, PNG, WebP or GIF) from a public URL or base64 data. Replaces whatever image the collection holds.")]
class SetPostImage extends Tool
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
            'url' => 'string|max:2048',
            'base64' => 'string',
            'filename' => 'string|max:255',
            'collection' => 'in:main,small',
        ], [
            'collection.in' => 'Collections are "main" (the featured image) or "small" (the listing thumbnail).',
        ]);

        $post = $this->findPost($validated, withTrashed: false);

        if ($post === null) {
            return Response::error($this->postNotFoundMessage($validated));
        }

        $collection = $validated['collection'] ?? (string) config('content-manager.media.collection', 'main');

        try {
            if (! empty($validated['url'])) {
                $image = RemoteImage::fetch($validated['url']);
            } elseif (! empty($validated['base64'])) {
                $image = RemoteImage::decode($validated['base64']);
            } else {
                return Response::error('Pass either a url or base64 image data.');
            }
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        }

        // The name comes from the caller, the extension from the bytes.
        $stem = Str::slug(pathinfo((string) ($validated['filename'] ?? $post->slug), PATHINFO_FILENAME)) ?: 'image';

        try {
            $post->addMediaFromString($image['bytes'])
                ->usingFileName($stem.'.'.$image['extension'])
                ->toMediaCollection($collection);
        } catch (Throwable) {
            return Response::error('The image could not be stored.');
        }

        // A new media row leaves the post itself untouched, so announce it.
        $this->announceChange($post);

        $this->recordMutation('post image set', ['slug' => $post->slug, 'collection' => $collection]);

        return Response::structured([
            'slug' => (string) $post->slug,
            'collection' => $collection,
            'image_url' => $post->refresh()->getFirstMediaUrl($collection),
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Slug of the post. Either slug or id is required.'),
            'id' => $schema->integer()->description('Id of the post.'),
            'url' => $schema->string()->description('Public http(s) URL of the image to fetch.'),
            'base64' => $schema->string()->description('Base64-encoded image data, as an alternative to url.'),
            'filename' => $schema->string()->description('Stored file name without extension; the extension follows the image type. Defaults to the post slug.'),
            'collection' => $schema->string()->enum(['main', 'small'])->description('Which image slot to fill.')->default('main'),
        ];
    }
}
