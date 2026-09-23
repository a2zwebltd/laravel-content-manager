<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Concerns;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Database\Eloquent\Model;

trait ResolvesRecords
{
    /** @param array<string, mixed> $input */
    protected function findPost(array $input, bool $withTrashed = true): ?Model
    {
        $query = Models::blogPost()::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if (! empty($input['id'])) {
            return $query->find($input['id']);
        }

        if (! empty($input['slug'])) {
            return $query->where('slug', $input['slug'])->first();
        }

        return null;
    }

    /** @param array<string, mixed> $input */
    protected function postNotFoundMessage(array $input): string
    {
        $identifier = $input['slug'] ?? $input['id'] ?? null;

        return $identifier === null
            ? 'Pass either a slug or an id to identify the post.'
            : sprintf('No post matches "%s". Use list-posts to see what exists.', $identifier);
    }

    /** @param array<string, mixed> $input */
    protected function findPage(array $input): ?Model
    {
        $query = Models::page()::query();

        if (! empty($input['id'])) {
            return $query->find($input['id']);
        }

        if (! empty($input['slug'])) {
            return $query->where('slug', $input['slug'])->first();
        }

        return null;
    }

    /**
     * Attach categories and tags by slug, creating tags that don't exist yet
     * (categories must already exist — they are an editorial decision, not
     * something an agent should invent mid-draft).
     *
     * Pivot writes never mark the model dirty, so on their own they fire no
     * Eloquent event — and therefore no ContentSaved, no response-cache flush
     * and no on_change hook. When a sync really changed something, the change
     * is announced explicitly, like any other edit. A caller that
     * still has unsaved attribute changes pending is left to announce it with
     * its own save(), which then fires once, after the pivots are in place.
     *
     * @param  array<int, string>|null  $categorySlugs
     * @param  array<int, string>|null  $tagSlugs
     * @return array<int, string> Slugs that could not be matched.
     */
    protected function syncTaxonomy(Model $post, ?array $categorySlugs, ?array $tagSlugs): array
    {
        $missing = [];
        $changed = false;

        if ($categorySlugs !== null) {
            $categories = Models::contentCategory()::query()
                ->whereIn('slug', $categorySlugs)
                ->get();

            $missing = array_values(array_diff($categorySlugs, $categories->pluck('slug')->all()));

            $changed = $this->syncChangedSomething($post->categories()->sync($categories->pluck('id')->all())) || $changed;
        }

        if ($tagSlugs !== null) {
            $ids = [];

            foreach ($tagSlugs as $slug) {
                $tag = Models::tag()::query()->firstOrCreate(
                    ['slug' => $slug],
                    ['name' => str($slug)->replace('-', ' ')->title()->toString(), 'type' => 'blog'],
                );

                $ids[] = $tag->id;
            }

            $changed = $this->syncChangedSomething($post->tags()->sync($ids)) || $changed;
        }

        if ($changed && ! $post->isDirty()) {
            $this->announceChange($post);
        }

        return $missing;
    }

    /**
     * Fire ContentSaved for a change that left the model's own columns alone,
     * so the response cache, on_change hooks and listeners still hear of it.
     */
    protected function announceChange(Model $model): void
    {
        if (method_exists($model, 'announceContentChange')) {
            $model->announceContentChange();

            return;
        }

        $model->touch();
    }

    /** @param  array<string, array<int, mixed>>  $result  What BelongsToMany::sync() returns. */
    private function syncChangedSomething(array $result): bool
    {
        return ($result['attached'] ?? []) !== []
            || ($result['detached'] ?? []) !== []
            || ($result['updated'] ?? []) !== [];
    }
}
