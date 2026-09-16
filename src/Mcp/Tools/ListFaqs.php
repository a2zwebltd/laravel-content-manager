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

#[Name('list-faqs')]
#[Description('List FAQ entries in display order, optionally limited to one group.')]
class ListFaqs extends Tool
{
    use PresentsContent;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'status' => 'in:any,published,draft',
            'group' => 'string|max:255',
        ]);

        $query = Models::faq()::query()->orderBy('sort_order');

        match ($validated['status'] ?? 'any') {
            'published' => $query->published(),
            'draft' => $query->whereNull('published_at'),
            default => null,
        };

        if ($group = $validated['group'] ?? null) {
            $query->group($group);
        }

        return Response::structured([
            'faqs' => $query->get()->map(fn ($faq) => $this->presentFaq($faq))->all(),
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(['any', 'published', 'draft'])->default('any'),
            'group' => $schema->string()->description('Only entries in this group, e.g. "pricing".'),
        ];
    }
}
