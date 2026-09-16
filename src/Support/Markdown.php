<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Support;

use Illuminate\Support\Str;

/**
 * One place for turning stored markdown into HTML and for estimating how long
 * a post takes to read, so the website, the feed and the PDF never disagree.
 */
class Markdown
{
    public static function render(?string $markdown): string
    {
        if (blank($markdown)) {
            return '';
        }

        return Str::markdown($markdown);
    }

    /** Whole minutes at the configured reading speed, never less than one. */
    public static function readingTime(?string $markdown): int
    {
        $wpm = max(1, (int) config('content-manager.blog.words_per_minute', 220));

        return max(1, (int) round(self::wordCount($markdown) / $wpm));
    }

    public static function wordCount(?string $markdown): int
    {
        return str_word_count(strip_tags((string) $markdown));
    }

    /** Plain text, for meta descriptions and search snippets. */
    public static function excerpt(?string $markdown, int $characters = 220): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags(self::render($markdown))) ?? '');

        return Str::limit($text, $characters);
    }
}
