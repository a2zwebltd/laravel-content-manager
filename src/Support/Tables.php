<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Support;

/**
 * Single resolver for table names, shared by the migrations, the models and
 * the Nova resources so a host that renames or prefixes a table only has to
 * say so once, in config.
 */
class Tables
{
    public static function blogPosts(): string
    {
        return self::resolve('blog_posts');
    }

    public static function contentCategories(): string
    {
        return self::resolve('content_categories');
    }

    public static function blogPostContentCategory(): string
    {
        return self::resolve('blog_post_content_category');
    }

    public static function tags(): string
    {
        return self::resolve('tags');
    }

    public static function taggables(): string
    {
        return self::resolve('taggables');
    }

    public static function pages(): string
    {
        return self::resolve('pages');
    }

    public static function faqs(): string
    {
        return self::resolve('faqs');
    }

    public static function chunks(): string
    {
        return self::resolve('chunks');
    }

    private static function resolve(string $key): string
    {
        return (string) config('content-manager.tables.prefix', '')
            .(string) config('content-manager.tables.'.$key, $key);
    }
}
