<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\Feeds\FeedableBlogPost;

// Switching the feed on means pointing the model map at the feedable subclass —
// exactly what a host does in its own config.
beforeEach(fn () => config()->set('content-manager.models.blog_post', FeedableBlogPost::class));

it('turns a published post into a feed item pointing at its url', function (): void {
    $post = FeedableBlogPost::factory()->published()->create([
        'title' => 'A syndicated post',
        'intro' => 'The summary',
    ]);

    $item = $post->toFeedItem();

    expect($item->title)->toBe('A syndicated post')
        ->and($item->summary)->toBe('The summary')
        ->and($item->link)->toContain('/blog/'.$post->slug);
});

it('only syndicates published posts, newest first', function (): void {
    FeedableBlogPost::factory()->published()->create(['published_at' => now()->subWeek(), 'title' => 'Older']);
    FeedableBlogPost::factory()->published()->create(['published_at' => now()->subDay(), 'title' => 'Newer']);
    FeedableBlogPost::factory()->draft()->create(['title' => 'Draft']);

    $titles = FeedableBlogPost::getFeedItems()->pluck('title')->all();

    expect($titles)->toBe(['Newer', 'Older']);
});

it('credits the configured author', function (): void {
    config()->set('content-manager.feed.author', 'A2Z WEB');

    $post = FeedableBlogPost::factory()->published()->create();

    expect($post->toFeedItem()->authorName)->toBe('A2Z WEB');
});
