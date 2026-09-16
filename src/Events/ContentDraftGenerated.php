<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after an AI draft lands, carrying the token counts so a host can bill
 * or record the spend without the package knowing anything about its
 * accounting.
 */
class ContentDraftGenerated
{
    use Dispatchable;

    /** @param array<string, mixed> $topic */
    public function __construct(
        public Model $post,
        public array $topic,
        public string $provider,
        public ?string $model,
        public int $promptTokens,
        public int $completionTokens,
    ) {}
}
