<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Support;

use A2ZWeb\ContentManager\Models\BlogPost;
use A2ZWeb\ContentManager\Models\Chunk;
use A2ZWeb\ContentManager\Models\ContentCategory;
use A2ZWeb\ContentManager\Models\Faq;
use A2ZWeb\ContentManager\Models\Page;
use A2ZWeb\ContentManager\Models\Tag;
use Illuminate\Database\Eloquent\Model;

/**
 * Resolves the configured model classes. Relations go through here rather than
 * naming classes directly, so a host that subclasses a model (to add an author
 * relation, say) has one place to say so.
 */
class Models
{
    /** @return class-string<Model> */
    public static function blogPost(): string
    {
        return self::resolve('blog_post', BlogPost::class);
    }

    /** @return class-string<Model> */
    public static function contentCategory(): string
    {
        return self::resolve('content_category', ContentCategory::class);
    }

    /** @return class-string<Model> */
    public static function tag(): string
    {
        return self::resolve('tag', Tag::class);
    }

    /** @return class-string<Model> */
    public static function page(): string
    {
        return self::resolve('page', Page::class);
    }

    /** @return class-string<Model> */
    public static function faq(): string
    {
        return self::resolve('faq', Faq::class);
    }

    /** @return class-string<Model> */
    public static function chunk(): string
    {
        return self::resolve('chunk', Chunk::class);
    }

    /** @return array<string, class-string<Model>> */
    public static function all(): array
    {
        return [
            'blog_post' => self::blogPost(),
            'content_category' => self::contentCategory(),
            'tag' => self::tag(),
            'page' => self::page(),
            'faq' => self::faq(),
            'chunk' => self::chunk(),
        ];
    }

    /**
     * @param  class-string<Model>  $default
     * @return class-string<Model>
     */
    private static function resolve(string $key, string $default): string
    {
        /** @var class-string<Model> $class */
        $class = config('content-manager.models.'.$key) ?: $default;

        return $class;
    }
}
