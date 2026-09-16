<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Concerns;

use A2ZWeb\ContentManager\Mcp\ContentMcpContext;
use Illuminate\Support\Facades\Log;

trait AuthorizesAbilities
{
    protected function context(): ContentMcpContext
    {
        // Absent only when a tool is invoked outside an HTTP request (tests,
        // a local stdio server) — there is no key to scope, so allow both.
        return app()->bound(ContentMcpContext::class)
            ? app(ContentMcpContext::class)
            : new ContentMcpContext('local', ['read', 'write']);
    }

    protected function cannotWrite(): bool
    {
        return ! $this->context()->canWrite();
    }

    protected function readOnlyMessage(): string
    {
        return 'This API key is read-only, so it cannot change content. Ask the operator for a key with the "write" ability.';
    }

    /** Leaves an audit trail of who changed what. @param array<string, mixed> $details */
    protected function recordMutation(string $action, array $details = []): void
    {
        Log::info('content-manager: '.$action, [
            'key' => $this->context()->name,
            ...$details,
        ]);
    }
}
