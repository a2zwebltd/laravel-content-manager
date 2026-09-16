<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Ai;

/**
 * The default backlog: whatever sits in `content-manager.ai.topics`. Hosts
 * that would rather drive the queue from a database point
 * `content-manager.ai.topic_provider` at their own implementation.
 */
class ConfigTopicProvider implements TopicProvider
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return array_values(array_filter(
            (array) config('content-manager.ai.topics', []),
            static fn ($topic) => is_array($topic) && ! empty($topic['slug']),
        ));
    }
}
