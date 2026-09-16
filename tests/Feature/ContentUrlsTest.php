<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\Models\BlogPost;
use A2ZWeb\ContentManager\Models\ContentCategory;
use A2ZWeb\ContentManager\Models\Page;
use A2ZWeb\ContentManager\Models\Tag;
use A2ZWeb\ContentManager\Support\ContentIndex;
use A2ZWeb\ContentManager\Support\ContentUrls;

it('lists published post urls only', function (): void {
    $published = BlogPost::factory()->published()->create();
    BlogPost::factory()->draft()->create();

    $urls = ContentUrls::posts();

    expect($urls)->toHaveCount(1)
        ->and($urls[0]['loc'])->toContain('/blog/'.$published->slug);
});

it('only lists taxonomy terms that have published posts', function (): void {
    $used = ContentCategory::factory()->create();
    ContentCategory::factory()->create();
    BlogPost::factory()->published()->create()->categories()->attach($used);

    $usedTag = Tag::factory()->create();
    Tag::factory()->create();
    BlogPost::factory()->published()->create()->tags()->attach($usedTag);

    expect(ContentUrls::categories())->toHaveCount(1)
        ->and(ContentUrls::tags())->toHaveCount(1);
});

it('lists published pages', function (): void {
    Page::factory()->published()->create(['slug' => 'about']);
    Page::factory()->create(['slug' => 'privacy', 'published_at' => null]);

    $urls = ContentUrls::pages();

    expect($urls)->toHaveCount(1)
        ->and($urls[0]['loc'])->toContain('/about');
});

it('builds a discovery index with and without bodies', function (): void {
    BlogPost::factory()->published()->create(['title' => 'A post', 'content' => 'Body copy']);

    expect(ContentIndex::posts()[0])->not->toHaveKey('content')
        ->and(ContentIndex::posts(withContent: true)[0]['content'])->toBe('Body copy');
});
