<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\Concerns\DrainsAgentStream;

/*
 * laravel/ai splits input into promptTokens (uncached), cache writes and cache
 * reads. Reporting promptTokens alone made a cached draft prompt look like a
 * handful of tokens in ContentDraftGenerated, and hosts under-booked the spend.
 */

function drainer(): object
{
    return new class
    {
        use DrainsAgentStream {
            drainStream as public;
        }
    };
}

function streamWithUsage(object $usage): IteratorAggregate
{
    return new class($usage) implements IteratorAggregate
    {
        public string $text = '{"title":"Draft"}';

        public function __construct(public object $usage) {}

        public function getIterator(): Iterator
        {
            yield 'chunk';
        }
    };
}

it('counts cached and cache-written input tokens as prompt tokens', function (): void {
    $result = drainer()->drainStream(streamWithUsage((object) [
        'promptTokens' => 3,
        'cacheWriteInputTokens' => 997,
        'cacheReadInputTokens' => 4000,
        'completionTokens' => 200,
    ]));

    expect($result)->toBe([
        'text' => '{"title":"Draft"}',
        'promptTokens' => 5000,
        'completionTokens' => 200,
    ]);
});

it('tolerates usage objects without cache fields', function (): void {
    $result = drainer()->drainStream(streamWithUsage((object) [
        'promptTokens' => 120,
        'completionTokens' => 40,
    ]));

    expect($result['promptTokens'])->toBe(120)
        ->and($result['completionTokens'])->toBe(40);
});
