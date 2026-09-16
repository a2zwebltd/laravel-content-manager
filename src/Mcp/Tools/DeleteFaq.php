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

#[Name('delete-faq')]
#[Description('Permanently delete an FAQ entry. FAQ entries are not soft-deleted, so this cannot be undone.')]
class DeleteFaq extends Tool
{
    use AuthorizesAbilities;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($this->cannotWrite()) {
            return Response::error($this->readOnlyMessage());
        }

        $validated = $request->validate(['id' => 'required|integer']);

        $faq = Models::faq()::query()->find($validated['id']);

        if ($faq === null) {
            return Response::error(sprintf('No FAQ entry with id %d.', $validated['id']));
        }

        $faq->delete();

        $this->recordMutation('faq deleted', ['id' => $validated['id']]);

        return Response::structured(['deleted' => true, 'id' => (int) $validated['id']]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Id of the entry to delete.')->required(),
        ];
    }
}
