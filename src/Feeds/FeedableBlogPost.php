<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Feeds;

use A2ZWeb\ContentManager\Models\BlogPost;
use A2ZWeb\ContentManager\Support\Routes;
use Illuminate\Support\Collection;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;

/**
 * The blog post as an Atom/RSS item.
 *
 * Kept as a subclass rather than folded into BlogPost because spatie/laravel-feed
 * is optional: a host without it would otherwise load a class implementing an
 * interface that does not exist. Point `config('feed.feeds.main.items')` and
 * `content-manager.models.blog_post` at this class to switch the feed on.
 */
class FeedableBlogPost extends BlogPost implements Feedable
{
    public function toFeedItem(): FeedItem
    {
        $url = Routes::url('show', $this->slug) ?? url('/');

        return FeedItem::create()
            ->id($url)
            ->title((string) $this->title)
            ->summary((string) ($this->intro ?? $this->subtitle ?? ''))
            ->updated($this->updated_at ?? $this->published_at)
            ->link($url)
            ->authorName((string) config('content-manager.feed.author', config('app.name')));
    }

    /** @return Collection<int, static> */
    public static function getFeedItems(): Collection
    {
        return static::query()
            ->published()
            ->latest('published_at')
            ->limit((int) config('content-manager.feed.limit', 50))
            ->get();
    }
}
