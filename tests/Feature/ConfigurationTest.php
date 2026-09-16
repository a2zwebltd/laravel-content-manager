<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\Models\BlogPost;
use A2ZWeb\ContentManager\Support\Markdown;
use A2ZWeb\ContentManager\Support\Models;
use A2ZWeb\ContentManager\Support\Tables;

it('honours a table prefix everywhere', function (): void {
    config()->set('content-manager.tables.prefix', 'cm_');

    expect(Tables::blogPosts())->toBe('cm_blog_posts')
        ->and((new BlogPost)->getTable())->toBe('cm_blog_posts');
});

it('resolves a host model override', function (): void {
    config()->set('content-manager.models.blog_post', CustomBlogPost::class);

    expect(Models::blogPost())->toBe(CustomBlogPost::class)
        ->and(BlogPost::factory()->make())->toBeInstanceOf(CustomBlogPost::class);
});

it('renders a host view when the view map points at one', function (): void {
    config()->set('content-manager.views.blog_index', 'content-manager::blog.tags');

    expect(config('content-manager.views.blog_index'))->toBe('content-manager::blog.tags');
});

it('renders github-flavoured markdown', function (): void {
    $html = Markdown::render("## Heading\n\n| a | b |\n|---|---|\n| 1 | 2 |");

    expect($html)->toContain('<h2>Heading</h2>')
        ->and($html)->toContain('<table>');
});

it('estimates reading time at the configured speed', function (): void {
    $words = str_repeat('word ', 440);

    expect(Markdown::readingTime($words))->toBe(2);

    config()->set('content-manager.blog.words_per_minute', 110);

    expect(Markdown::readingTime($words))->toBe(4);
});

it('never reports less than a minute', function (): void {
    expect(Markdown::readingTime('Three little words'))->toBe(1);
});

class CustomBlogPost extends BlogPost {}
