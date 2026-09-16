<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\Models\BlogPost;
use A2ZWeb\ContentManager\Models\ContentCategory;
use A2ZWeb\ContentManager\Models\Tag;

it('lists published posts and hides drafts', function (): void {
    $published = BlogPost::factory()->published()->create(['title' => 'A published post']);
    BlogPost::factory()->draft()->create(['title' => 'A draft post']);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee($published->title)
        ->assertDontSee('A draft post');
});

it('hides posts scheduled for the future', function (): void {
    BlogPost::factory()->scheduled()->create(['title' => 'Tomorrow']);

    $this->get(route('blog.index'))->assertDontSee('Tomorrow');
});

it('shows a published post', function (): void {
    $post = BlogPost::factory()->published()->create([
        'content' => "## A heading\n\nSome body copy.",
    ]);

    $this->get(route('blog.show', $post->slug))
        ->assertOk()
        ->assertSee($post->title)
        ->assertSee('<h2>A heading</h2>', false);
});

it('404s on a draft post', function (): void {
    $post = BlogPost::factory()->draft()->create();

    $this->get(route('blog.show', $post->slug))->assertNotFound();
});

it('searches across title, intro and body', function (): void {
    BlogPost::factory()->published()->create(['title' => 'Needle in the title']);
    BlogPost::factory()->published()->create(['title' => 'Body match', 'content' => 'contains a needle here']);
    BlogPost::factory()->published()->create(['title' => 'Unrelated', 'intro' => 'nothing', 'content' => 'nothing']);

    $response = $this->get(route('blog.index', ['q' => 'needle']));

    $response->assertOk()
        ->assertSee('Needle in the title')
        ->assertSee('Body match')
        ->assertDontSee('Unrelated');
});

it('filters by category', function (): void {
    $category = ContentCategory::factory()->create(['name' => 'Tutorials', 'slug' => 'tutorials']);
    $inCategory = BlogPost::factory()->published()->create(['title' => 'Filed under tutorials']);
    $inCategory->categories()->attach($category);
    BlogPost::factory()->published()->create(['title' => 'Somewhere else']);

    $this->get(route('blog.category', 'tutorials'))
        ->assertOk()
        ->assertSee('Filed under tutorials')
        ->assertDontSee('Somewhere else');
});

it('filters by tag', function (): void {
    $tag = Tag::factory()->create(['name' => 'GEO', 'slug' => 'geo']);
    $tagged = BlogPost::factory()->published()->create(['title' => 'Tagged post']);
    $tagged->tags()->attach($tag);
    BlogPost::factory()->published()->create(['title' => 'Untagged post']);

    $this->get(route('blog.tag', 'geo'))
        ->assertOk()
        ->assertSee('Tagged post')
        ->assertDontSee('Untagged post');
});

it('lists tags that have published posts', function (): void {
    $used = Tag::factory()->create(['name' => 'Used', 'slug' => 'used']);
    Tag::factory()->create(['name' => 'Unused', 'slug' => 'unused']);
    BlogPost::factory()->published()->create()->tags()->attach($used);

    $this->get(route('blog.tags'))
        ->assertOk()
        ->assertSee('Used')
        ->assertDontSee('Unused');
});

it('offers related posts and chronological neighbours', function (): void {
    $tag = Tag::factory()->create(['slug' => 'shared']);

    $older = BlogPost::factory()->published()->create(['title' => 'Older post', 'published_at' => now()->subDays(10)]);
    $post = BlogPost::factory()->published()->create(['title' => 'Middle post', 'published_at' => now()->subDays(5)]);
    $newer = BlogPost::factory()->published()->create(['title' => 'Newer post', 'published_at' => now()->subDay()]);

    $post->tags()->attach($tag);
    $older->tags()->attach($tag);

    $this->get(route('blog.show', $post->slug))
        ->assertOk()
        ->assertSee('Older post')
        ->assertSee('Newer post');
});
