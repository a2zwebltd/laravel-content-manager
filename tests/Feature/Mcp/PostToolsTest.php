<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\Events\ContentSaved;
use A2ZWeb\ContentManager\Mcp\ContentServer;
use A2ZWeb\ContentManager\Mcp\Tools\CreatePost;
use A2ZWeb\ContentManager\Mcp\Tools\DeletePost;
use A2ZWeb\ContentManager\Mcp\Tools\GetPost;
use A2ZWeb\ContentManager\Mcp\Tools\ListPosts;
use A2ZWeb\ContentManager\Mcp\Tools\PublishPost;
use A2ZWeb\ContentManager\Mcp\Tools\RestorePost;
use A2ZWeb\ContentManager\Mcp\Tools\SetPostImage;
use A2ZWeb\ContentManager\Mcp\Tools\UnpublishPost;
use A2ZWeb\ContentManager\Mcp\Tools\UpdatePost;
use A2ZWeb\ContentManager\Models\BlogPost;
use A2ZWeb\ContentManager\Models\ContentCategory;
use A2ZWeb\ContentManager\Models\Tag;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;

it('lists published posts only when asked', function (): void {
    BlogPost::factory()->published()->count(3)->create();
    BlogPost::factory()->draft()->create();

    ContentServer::tool(ListPosts::class, ['status' => 'published'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json->where('total', 3)->etc());
});

it('filters a listing by tag slug', function (): void {
    $tag = Tag::factory()->create(['slug' => 'geo']);
    BlogPost::factory()->published()->create(['title' => 'Tagged'])->tags()->attach($tag);
    BlogPost::factory()->published()->create(['title' => 'Untagged']);

    ContentServer::tool(ListPosts::class, ['tag' => 'geo'])
        ->assertOk()
        ->assertSee('Tagged');
});

it('gets a post by slug with its body', function (): void {
    $post = BlogPost::factory()->published()->create(['content' => 'The body copy']);

    ContentServer::tool(GetPost::class, ['slug' => $post->slug])
        ->assertOk()
        ->assertSee('The body copy');
});

it('explains itself when the post does not exist', function (): void {
    ContentServer::tool(GetPost::class, ['slug' => 'nope'])
        ->assertHasErrors()
        ->assertSee('No post matches');
});

it('creates a draft post with taxonomy', function (): void {
    ContentCategory::factory()->create(['slug' => 'seo', 'name' => 'SEO']);

    ContentServer::tool(CreatePost::class, [
        'title' => 'How AI engines pick sources',
        'content' => '## First heading',
        'category_slugs' => ['seo'],
        'tag_slugs' => ['citations'],
    ])->assertOk();

    $post = BlogPost::query()->firstOrFail();

    expect($post->slug)->toBe('how-ai-engines-pick-sources')
        ->and($post->published_at)->toBeNull()
        ->and($post->categories->pluck('slug')->all())->toBe(['seo'])
        ->and($post->tags->pluck('slug')->all())->toBe(['citations']);
});

it('reports category slugs it could not match', function (): void {
    ContentServer::tool(CreatePost::class, [
        'title' => 'A post',
        'content' => 'Body',
        'category_slugs' => ['does-not-exist'],
    ])
        ->assertOk()
        ->assertSee('does-not-exist');
});

it('refuses to reuse a slug held by a soft-deleted post', function (): void {
    $post = BlogPost::factory()->create(['slug' => 'taken']);
    $post->delete();

    ContentServer::tool(CreatePost::class, [
        'title' => 'Taken',
        'slug' => 'taken',
        'content' => 'Body',
    ])
        ->assertHasErrors()
        ->assertSee('soft-deleted');
});

it('updates only the fields it is given', function (): void {
    $post = BlogPost::factory()->published()->create([
        'title' => 'Original title',
        'intro' => 'Original intro',
    ]);

    ContentServer::tool(UpdatePost::class, [
        'slug' => $post->slug,
        'title' => 'New title',
    ])->assertOk();

    $post->refresh();

    expect($post->title)->toBe('New title')
        ->and($post->intro)->toBe('Original intro');
});

it('announces a change that only touches the taxonomy', function (): void {
    ContentCategory::factory()->create(['slug' => 'seo']);
    $post = BlogPost::factory()->published()->create();

    Event::fake([ContentSaved::class]);

    ContentServer::tool(UpdatePost::class, [
        'slug' => $post->slug,
        'category_slugs' => ['seo'],
    ])->assertOk();

    Event::assertDispatchedTimes(ContentSaved::class, 1);
    Event::assertDispatched(ContentSaved::class, fn (ContentSaved $e) => $e->model->is($post));
});

it('stays quiet when the taxonomy it is given is already in place', function (): void {
    $tag = Tag::factory()->create(['slug' => 'geo']);
    $post = BlogPost::factory()->published()->create();
    $post->tags()->attach($tag);

    Event::fake([ContentSaved::class]);

    ContentServer::tool(UpdatePost::class, [
        'slug' => $post->slug,
        'tag_slugs' => ['geo'],
    ])->assertOk();

    Event::assertNotDispatched(ContentSaved::class);
});

it('announces a field and taxonomy change once, after the pivots are written', function (): void {
    Tag::factory()->create(['slug' => 'geo']);
    $post = BlogPost::factory()->published()->create();

    $seen = [];
    Event::listen(ContentSaved::class, function (ContentSaved $event) use (&$seen): void {
        if ($event->type() === 'blog_post') {
            $seen[] = $event->model->tags()->pluck('slug')->all();
        }
    });

    ContentServer::tool(UpdatePost::class, [
        'slug' => $post->slug,
        'title' => 'Retitled',
        'tag_slugs' => ['geo'],
    ])->assertOk();

    expect($seen)->toBe([['geo']])
        ->and($post->refresh()->title)->toBe('Retitled');
});

it('announces a new featured image', function (): void {
    $post = BlogPost::factory()->published()->create();

    Event::fake([ContentSaved::class]);

    $png = base64_encode((string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));

    ContentServer::tool(SetPostImage::class, [
        'slug' => $post->slug,
        'base64' => $png,
        'filename' => 'pixel.png',
    ])->assertOk();

    Event::assertDispatchedTimes(ContentSaved::class, 1);
});

it('publishes and unpublishes a post', function (): void {
    $post = BlogPost::factory()->draft()->create();

    ContentServer::tool(PublishPost::class, ['slug' => $post->slug])->assertOk();
    expect($post->refresh()->isPublished())->toBeTrue();

    ContentServer::tool(UnpublishPost::class, ['slug' => $post->slug])->assertOk();
    expect($post->refresh()->isPublished())->toBeFalse();
});

it('soft-deletes by default and restores', function (): void {
    $post = BlogPost::factory()->published()->create();

    ContentServer::tool(DeletePost::class, ['slug' => $post->slug])->assertOk();
    expect(BlogPost::query()->count())->toBe(0)
        ->and(BlogPost::withTrashed()->count())->toBe(1);

    ContentServer::tool(RestorePost::class, ['slug' => $post->slug])->assertOk();
    expect(BlogPost::query()->count())->toBe(1);
});

it('force-deletes when asked', function (): void {
    $post = BlogPost::factory()->published()->create();

    ContentServer::tool(DeletePost::class, ['slug' => $post->slug, 'force' => true])->assertOk();

    expect(BlogPost::withTrashed()->count())->toBe(0);
});
