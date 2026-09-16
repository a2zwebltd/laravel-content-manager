<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\Mcp\ContentServer;
use A2ZWeb\ContentManager\Mcp\Tools\ContentStats;
use A2ZWeb\ContentManager\Mcp\Tools\CreateCategory;
use A2ZWeb\ContentManager\Mcp\Tools\CreateTag;
use A2ZWeb\ContentManager\Mcp\Tools\DeleteFaq;
use A2ZWeb\ContentManager\Mcp\Tools\GetPage;
use A2ZWeb\ContentManager\Mcp\Tools\ListCategories;
use A2ZWeb\ContentManager\Mcp\Tools\ListChunks;
use A2ZWeb\ContentManager\Mcp\Tools\ListFaqs;
use A2ZWeb\ContentManager\Mcp\Tools\ListPages;
use A2ZWeb\ContentManager\Mcp\Tools\ListTags;
use A2ZWeb\ContentManager\Mcp\Tools\UpsertChunk;
use A2ZWeb\ContentManager\Mcp\Tools\UpsertFaq;
use A2ZWeb\ContentManager\Mcp\Tools\UpsertPage;
use A2ZWeb\ContentManager\Models\BlogPost;
use A2ZWeb\ContentManager\Models\Chunk;
use A2ZWeb\ContentManager\Models\ContentCategory;
use A2ZWeb\ContentManager\Models\Faq;
use A2ZWeb\ContentManager\Models\Page;
use Illuminate\Testing\Fluent\AssertableJson;

it('lists categories with published post counts', function (): void {
    $category = ContentCategory::factory()->create(['name' => 'Tutorials']);
    BlogPost::factory()->published()->create()->categories()->attach($category);
    BlogPost::factory()->draft()->create()->categories()->attach($category);

    ContentServer::tool(ListCategories::class, [])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json->has('categories.0', fn (AssertableJson $category) => $category->where('published_posts', 1)->etc())->etc());
});

it('creates a category idempotently', function (): void {
    ContentServer::tool(CreateCategory::class, ['name' => 'Market Research'])->assertOk();
    ContentServer::tool(CreateCategory::class, ['name' => 'Market Research'])->assertOk();

    expect(ContentCategory::query()->count())->toBe(1);
});

it('creates and lists tags', function (): void {
    ContentServer::tool(CreateTag::class, ['name' => 'Citations'])->assertOk();

    ContentServer::tool(ListTags::class, [])
        ->assertOk()
        ->assertSee('citations');
});

it('upserts a page and toggles publication', function (): void {
    ContentServer::tool(UpsertPage::class, [
        'slug' => 'about',
        'title' => 'About us',
        'content' => 'We do things.',
        'publish' => true,
    ])->assertOk();

    expect(Page::query()->where('slug', 'about')->first()?->isPublished())->toBeTrue();

    ContentServer::tool(UpsertPage::class, ['slug' => 'about', 'publish' => false])->assertOk();

    expect(Page::query()->where('slug', 'about')->first()?->isPublished())->toBeFalse();
});

it('refuses to create a page without a body', function (): void {
    ContentServer::tool(UpsertPage::class, ['slug' => 'new-page', 'title' => 'Only a title'])
        ->assertHasErrors()
        ->assertSee('markdown body');
});

it('reads a page including its body', function (): void {
    Page::factory()->published()->create(['slug' => 'terms', 'content' => 'The terms text']);

    ContentServer::tool(GetPage::class, ['slug' => 'terms'])
        ->assertOk()
        ->assertSee('The terms text');
});

it('lists and upserts faqs', function (): void {
    ContentServer::tool(UpsertFaq::class, [
        'question' => 'What does it cost?',
        'answer' => 'Less than you think.',
        'group' => 'pricing',
    ])->assertOk();

    $faq = Faq::query()->firstOrFail();

    expect($faq->published_at)->not->toBeNull();

    ContentServer::tool(ListFaqs::class, ['group' => 'pricing'])
        ->assertOk()
        ->assertSee('What does it cost?');

    ContentServer::tool(DeleteFaq::class, ['id' => $faq->id])->assertOk();

    expect(Faq::query()->count())->toBe(0);
});

it('upserts a chunk and invalidates its cache', function (): void {
    Chunk::factory()->create(['code' => 'cta', 'content' => 'Old copy']);

    expect(Chunk::getByCode('cta'))->toBe('Old copy');

    ContentServer::tool(UpsertChunk::class, ['code' => 'cta', 'content' => 'New copy'])->assertOk();

    expect(Chunk::getByCode('cta'))->toBe('New copy');
});

it('lists chunks', function (): void {
    Chunk::factory()->create(['code' => 'banner', 'name' => 'Home banner']);

    ContentServer::tool(ListChunks::class, [])
        ->assertOk()
        ->assertSee('Home banner');
});

it('lists pages', function (): void {
    Page::factory()->published()->create(['slug' => 'about', 'title' => 'About']);

    ContentServer::tool(ListPages::class, ['status' => 'published'])
        ->assertOk()
        ->assertSee('about');
});

it('reports content stats', function (): void {
    BlogPost::factory()->published()->count(2)->create();
    BlogPost::factory()->draft()->create();

    ContentServer::tool(ContentStats::class, [])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json->has('posts', fn (AssertableJson $posts) => $posts->where('published', 2)->where('drafts', 1)->etc())->etc());
});
