<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Http\Controllers;

use A2ZWeb\ContentManager\Support\Models;
use A2ZWeb\ContentManager\Support\Tables;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * The public blog. Methods are protected rather than private throughout so a
 * host can subclass and adjust one piece without reimplementing the rest.
 *
 * View data contract (frozen — host views depend on it):
 *   index:    posts, search, categories, tags, totalPosts
 *   show:     post, related, previousPost, nextPost, categories, tags, totalPosts
 *   category: category, posts, categories, tags, totalPosts
 *   tag:      tag, posts, categories, tags, totalPosts
 *   tags:     groupedTags
 */
class BlogController extends Controller
{
    public function index(): View
    {
        /*
         * Search is a plain LIKE across title, intro and body. For an archive
         * of this size that is entirely adequate and costs no infrastructure —
         * a search engine would be a dependency for a table a full scan
         * crosses in microseconds.
         */
        $search = trim((string) request('q'));

        $query = Models::blogPost()::query()
            ->published()
            ->with(['categories', 'tags', 'media'])
            ->latest('published_at');

        if ($search !== '') {
            $escaped = '%'.addcslashes($search, '%_\\').'%';

            $query->where(fn ($q) => $q
                ->where('title', 'like', $escaped)
                ->orWhere('intro', 'like', $escaped)
                ->orWhere('content', 'like', $escaped));
        }

        $perPage = (int) config('content-manager.blog.per_page', 12);

        return view(config('content-manager.views.blog_index'), [
            'posts' => $query->paginate($perPage)->withQueryString(),
            'search' => $search,
            ...$this->browseData(),
        ]);
    }

    public function show(string $blogPost): View
    {
        $post = Models::blogPost()::query()
            ->where('slug', $blogPost)
            ->firstOrFail();

        abort_unless($post->isPublished(), 404);

        $post->load(['categories', 'tags', 'media']);

        return view(config('content-manager.views.blog_show'), [
            'post' => $post,
            'related' => $this->findRelated($post, (int) config('content-manager.blog.related_limit', 3)),
            // Chronological neighbours, so a reader can walk the archive without
            // going back to the index. `related` answers "more like this";
            // these answer "what came next".
            'previousPost' => Models::blogPost()::query()
                ->published()
                ->where('published_at', '<', $post->published_at)
                ->latest('published_at')
                ->first(),
            'nextPost' => Models::blogPost()::query()
                ->published()
                ->where('published_at', '>', $post->published_at)
                ->oldest('published_at')
                ->first(),
            ...$this->browseData(),
        ]);
    }

    public function tag(string $tag): View
    {
        $model = Models::tag()::query()->where('slug', $tag)->firstOrFail();

        return view(config('content-manager.views.blog_tag'), [
            'tag' => $model,
            'posts' => Models::blogPost()::query()
                ->published()
                ->whereHas('tags', fn ($q) => $q->where(Tables::tags().'.id', $model->id))
                ->with(['categories', 'tags', 'media'])
                ->latest('published_at')
                ->paginate((int) config('content-manager.blog.per_page', 12)),
            ...$this->browseData(),
        ]);
    }

    public function category(string $contentCategory): View
    {
        $model = Models::contentCategory()::query()->where('slug', $contentCategory)->firstOrFail();

        return view(config('content-manager.views.blog_category'), [
            'category' => $model,
            'posts' => Models::blogPost()::query()
                ->published()
                ->whereHas('categories', fn ($q) => $q->where(Tables::contentCategories().'.id', $model->id))
                ->with(['categories', 'tags', 'media'])
                ->latest('published_at')
                ->paginate((int) config('content-manager.blog.per_page', 12)),
            ...$this->browseData(),
        ]);
    }

    public function tags(): View
    {
        $tags = Models::tag()::query()
            ->whereHas('blogPosts', fn ($q) => $q->published())
            ->withCount(['blogPosts' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($tag) => mb_strtoupper(mb_substr((string) $tag->name, 0, 1)));

        return view(config('content-manager.views.blog_tags'), [
            'groupedTags' => $tags,
        ]);
    }

    /**
     * Categories, tags and the archive total — everything the browse sidebar
     * needs, shared so index, category, tag and post pages all offer the same
     * navigation instead of each carrying a different subset.
     *
     * @return array{categories: mixed, tags: mixed, totalPosts: int}
     */
    protected function browseData(): array
    {
        return [
            'categories' => Models::contentCategory()::query()
                ->whereHas('blogPosts', fn ($q) => $q->published())
                ->withCount(['blogPosts' => fn ($q) => $q->published()])
                ->orderByDesc('blog_posts_count')
                ->get(),
            'tags' => Models::tag()::query()
                ->whereHas('blogPosts', fn ($q) => $q->published())
                ->withCount(['blogPosts' => fn ($q) => $q->published()])
                ->orderByDesc('blog_posts_count')
                ->orderBy('name')
                ->get(),
            'totalPosts' => Models::blogPost()::query()->published()->count(),
        ];
    }

    /**
     * Related posts: tag matches first, then category matches, then the latest
     * posts — so the slot is always filled.
     *
     * @return Collection<int, Model>
     */
    protected function findRelated(Model $post, int $limit): Collection
    {
        $excludeIds = [$post->id];
        $related = collect();

        $tagIds = $post->tags->pluck('id');

        if ($tagIds->isNotEmpty()) {
            $related = Models::blogPost()::query()
                ->published()
                ->whereNotIn('id', $excludeIds)
                ->whereHas('tags', fn ($q) => $q->whereIn(Tables::tags().'.id', $tagIds))
                ->with('media')
                ->latest('published_at')
                ->limit($limit)
                ->get();
        }

        if ($related->count() < $limit) {
            $excludeIds = array_merge($excludeIds, $related->pluck('id')->all());
            $categoryIds = $post->categories->pluck('id');

            if ($categoryIds->isNotEmpty()) {
                $related = $related->merge(
                    Models::blogPost()::query()
                        ->published()
                        ->whereNotIn('id', $excludeIds)
                        ->whereHas('categories', fn ($q) => $q->whereIn(Tables::contentCategories().'.id', $categoryIds))
                        ->with('media')
                        ->latest('published_at')
                        ->limit($limit - $related->count())
                        ->get()
                );
            }
        }

        if ($related->count() < $limit) {
            $excludeIds = array_merge($excludeIds, $related->pluck('id')->all());

            $related = $related->merge(
                Models::blogPost()::query()
                    ->published()
                    ->whereNotIn('id', $excludeIds)
                    ->with('media')
                    ->latest('published_at')
                    ->limit($limit - $related->count())
                    ->get()
            );
        }

        return new Collection($related->all());
    }
}
