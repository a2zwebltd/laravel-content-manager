<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Tools;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('list-chunks')]
#[Description('List reusable content chunks — named snippets templates drop in by code, such as banner copy or a CTA line.')]
class ListChunks extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate(['with_content' => 'boolean']);

        $withContent = $validated['with_content'] ?? false;

        return Response::structured([
            'chunks' => Models::chunk()::query()
                ->orderBy('code')
                ->get()
                ->map(fn ($chunk): array => array_filter([
                    'id' => $chunk->id,
                    'code' => (string) $chunk->code,
                    'name' => (string) $chunk->name,
                    'is_published' => (bool) $chunk->is_published,
                    'content' => $withContent ? (string) $chunk->content : null,
                ], static fn ($value) => $value !== null))
                ->all(),
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'with_content' => $schema->boolean()->description('Include each chunk body.')->default(false),
        ];
    }
}
