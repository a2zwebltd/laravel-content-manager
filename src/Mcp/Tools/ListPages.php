<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Tools;

use A2ZWeb\ContentManager\Mcp\Concerns\PresentsContent;
use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('list-pages')]
#[Description('List the CMS pages — about, privacy, terms and anything else the site serves from the database.')]
class ListPages extends Tool
{
    use PresentsContent;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate(['status' => 'in:any,published,draft']);

        $query = Models::page()::query()->orderBy('sort_order');

        match ($validated['status'] ?? 'any') {
            'published' => $query->published(),
            'draft' => $query->whereNull('published_at'),
            default => null,
        };

        return Response::structured([
            'pages' => $query->get()->map(fn ($page) => $this->presentPage($page))->all(),
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(['any', 'published', 'draft'])->default('any'),
        ];
    }
}
