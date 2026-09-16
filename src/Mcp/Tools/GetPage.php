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

#[Name('get-page')]
#[Description('Fetch one CMS page by slug or id, including its full markdown body.')]
class GetPage extends Tool
{
    use PresentsContent;
    use ResolvesRecords;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'slug' => 'string|max:255',
            'id' => 'integer',
        ]);

        $page = $this->findPage($validated);

        if ($page === null) {
            return Response::error('No page matches that slug or id. Use list-pages to see what exists.');
        }

        return Response::structured(['page' => $this->presentPage($page, withContent: true)]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Page slug, e.g. "privacy". Either slug or id is required.'),
            'id' => $schema->integer()->description('Page id.'),
        ];
    }
}
