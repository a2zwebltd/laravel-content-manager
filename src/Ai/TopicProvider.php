<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Ai;

interface TopicProvider
{
    /**
     * The editorial backlog, in the order it should be worked through. Each
     * topic needs a stable `slug` — that is what makes drafting idempotent.
     *
     * @return array<int, array{slug: string, title_idea: string, keyword?: string, angle?: string}>
     */
    public function all(): array;
}
