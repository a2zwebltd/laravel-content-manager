<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Ai;

use A2ZWeb\ContentManager\Support\Editorial;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Drafts a full post for the content pipeline.
 *
 * Returns one JSON object as text rather than using structured output, so the
 * call can be streamed — long-form generation outlives the idle connection
 * window, and streaming structured output is not supported. The caller parses
 * the JSON with DrainsAgentStream::parseDraftJson().
 */
class BlogDraftWriter implements Agent
{
    use Promptable;

    public function __construct(
        public string $titleIdea,
        public string $keyword = '',
        public string $angle = '',
    ) {}

    public function instructions(): Stringable|string
    {
        $limits = Editorial::limits();
        $words = (string) config('content-manager.ai.word_count', '1200-1800');

        $assignment = collect([
            'TOPIC: '.$this->titleIdea,
            $this->keyword === '' ? null : 'PRIMARY KEYWORD: '.$this->keyword,
            $this->angle === '' ? null : 'ANGLE / BRIEF: '.$this->angle,
        ])->filter()->implode("\n");

        return <<<INSTRUCTIONS
        You are the senior content writer for this site.

        {$this->guidelines()}

        Write a complete, publication-ready post.

        {$assignment}

        Length: {$words} words of genuinely useful, specific content. Concrete examples, checklists and copy-paste snippets where the topic calls for them.

        Respond with ONLY a JSON object (no markdown fence, no commentary) with exactly these keys:
        {
          "title": "Post title, max {$this->limit($limits, 'title', 65)} chars, includes the primary keyword naturally",
          "subtitle": "One-sentence subtitle expanding the promise, max 120 chars",
          "intro": "2-3 sentence teaser used in listings, max {$this->limit($limits, 'intro', 220)} chars, no markdown",
          "content": "The full post body in markdown. Do NOT repeat the title as an H1 — start with the first paragraph.",
          "meta_title": "SERP title tag, max {$this->limit($limits, 'meta_title', 60)} chars",
          "meta_description": "SERP meta description, max {$this->limit($limits, 'meta_description', 155)} chars, includes the keyword, ends with a reason to click",
          "meta_keywords": "5-8 comma-separated keywords/phrases"
        }
        INSTRUCTIONS;
    }

    private function guidelines(): string
    {
        return Editorial::guidelines();
    }

    /** @param array<string, mixed> $limits */
    private function limit(array $limits, string $key, int $default): int
    {
        return (int) ($limits[$key] ?? $default);
    }
}
