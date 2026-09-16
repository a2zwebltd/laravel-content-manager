<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Http\Middleware;

use A2ZWeb\ContentManager\Mcp\ContentMcpContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-token auth for the MCP endpoint, keyed off static keys in the
 * environment — no user table, no session, so an agent in any of our apps
 * authenticates the same way.
 */
class EnsureContentApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $presented = $this->presentedKey($request);

        if ($presented === '') {
            return $this->deny();
        }

        $matched = null;

        // Every configured key is compared, even after a hit: bailing early
        // would leak the key's position in the list through response timing.
        foreach ((array) config('content-manager.mcp.keys', []) as $candidate) {
            if (! is_array($candidate) || ! isset($candidate['key'])) {
                continue;
            }

            if (hash_equals((string) $candidate['key'], $presented)) {
                $matched = $candidate;
            }
        }

        if ($matched === null) {
            return $this->deny();
        }

        app()->instance(ContentMcpContext::class, new ContentMcpContext(
            name: (string) ($matched['name'] ?? 'unknown'),
            abilities: array_values((array) ($matched['abilities'] ?? ['read'])),
        ));

        return $next($request);
    }

    private function presentedKey(Request $request): string
    {
        $header = (string) $request->header('Authorization', '');

        if (str_starts_with(strtolower($header), 'bearer ')) {
            return trim(substr($header, 7));
        }

        return trim((string) $request->header('X-Content-Api-Key', ''));
    }

    private function deny(): Response
    {
        return response()->json([
            'error' => 'Invalid or missing API key.',
        ], 401, ['WWW-Authenticate' => 'Bearer']);
    }
}
