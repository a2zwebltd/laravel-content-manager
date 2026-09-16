<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Tools;

use A2ZWeb\ContentManager\Mcp\Concerns\AuthorizesAbilities;
use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('upsert-chunk')]
#[Description('Create or update a content chunk by code. Templates render chunks live, so an edit here changes the site immediately.')]
class UpsertChunk extends Tool
{
    use AuthorizesAbilities;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($this->cannotWrite()) {
            return Response::error($this->readOnlyMessage());
        }

        $validated = $request->validate([
            'code' => 'required|string|max:255',
            'name' => 'string|max:255',
            'content' => 'string',
            'is_published' => 'boolean',
        ]);

        $chunk = Models::chunk()::query()->firstOrNew(['code' => $validated['code']]);
        $isNew = ! $chunk->exists;

        $chunk->fill(array_filter([
            'name' => $validated['name'] ?? ($isNew ? $validated['code'] : null),
            'content' => $validated['content'] ?? null,
        ], static fn ($value) => $value !== null));

        if (array_key_exists('is_published', $validated)) {
            $chunk->is_published = $validated['is_published'];
        }

        $chunk->save();

        $this->recordMutation($isNew ? 'chunk created' : 'chunk updated', ['code' => $chunk->code]);

        return Response::structured([
            'chunk' => [
                'id' => $chunk->id,
                'code' => (string) $chunk->code,
                'name' => (string) $chunk->name,
                'is_published' => (bool) $chunk->is_published,
            ],
            'created' => $isNew,
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('The code templates reference this chunk by.')->required(),
            'name' => $schema->string()->description('Human label shown in the admin.'),
            'content' => $schema->string()->description('The chunk body.'),
            'is_published' => $schema->boolean()->description('Unpublished chunks render as empty.'),
        ];
    }
}
