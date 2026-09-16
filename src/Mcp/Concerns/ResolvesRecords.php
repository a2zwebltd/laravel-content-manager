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
     * @param  array<int, string>|null  $categorySlugs
     * @param  array<int, string>|null  $tagSlugs
     * @return array<int, string> Slugs that could not be matched.
     */
    protected function syncTaxonomy(Model $post, ?array $categorySlugs, ?array $tagSlugs): array
    {
        $missing = [];

        if ($categorySlugs !== null) {
            $categories = Models::contentCategory()::query()
                ->whereIn('slug', $categorySlugs)
                ->get();

            $missing = array_values(array_diff($categorySlugs, $categories->pluck('slug')->all()));

            $post->categories()->sync($categories->pluck('id')->all());
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

            $post->tags()->sync($ids);
        }

        return $missing;
    }
}
