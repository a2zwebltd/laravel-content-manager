<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Tools;

use A2ZWeb\ContentManager\Mcp\Concerns\AuthorizesAbilities;
use A2ZWeb\ContentManager\Mcp\Concerns\PresentsContent;
use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('upsert-faq')]
#[Description('Create an FAQ entry, or update one by id. Answers may contain HTML.')]
class UpsertFaq extends Tool
{
    use AuthorizesAbilities;
    use PresentsContent;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($this->cannotWrite()) {
            return Response::error($this->readOnlyMessage());
        }

        $validated = $request->validate([
            'id' => 'integer',
            'question' => 'string|max:255',
            'answer' => 'string',
            'group' => 'string|max:255',
            'sort_order' => 'integer|min:0',
            'publish' => 'boolean',
        ]);

        $faq = isset($validated['id'])
            ? Models::faq()::query()->find($validated['id'])
            : Models::faq()::query()->newModelInstance();

        if ($faq === null) {
            return Response::error(sprintf('No FAQ entry with id %d.', $validated['id']));
        }

        $isNew = ! $faq->exists;

        if ($isNew && (empty($validated['question']) || empty($validated['answer']))) {
            return Response::error('A new FAQ entry needs both a question and an answer.');
        }

        $faq->fill(array_filter([
            'question' => $validated['question'] ?? null,
            'answer' => $validated['answer'] ?? null,
            'group' => $validated['group'] ?? null,
            'sort_order' => $validated['sort_order'] ?? null,
        ], static fn ($value) => $value !== null));

        if (array_key_exists('publish', $validated)) {
            $faq->published_at = $validated['publish'] ? ($faq->published_at ?? now()) : null;
        } elseif ($isNew) {
            $faq->published_at = now();
        }

        $faq->save();

        $this->recordMutation($isNew ? 'faq created' : 'faq updated', ['id' => $faq->id]);

        return Response::structured([
            'faq' => $this->presentFaq($faq),
            'created' => $isNew,
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Id of an existing entry. Omit to create a new one.'),
            'question' => $schema->string()->description('The question. Required when creating.'),
            'answer' => $schema->string()->description('The answer — HTML allowed. Required when creating.'),
            'group' => $schema->string()->description('Group name, so a page can render just its own set.'),
            'sort_order' => $schema->integer()->description('Display order within the group.'),
            'publish' => $schema->boolean()->description('New entries publish immediately unless this is false.'),
        ];
    }
}
