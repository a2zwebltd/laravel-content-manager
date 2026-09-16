<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp;

/**
 * Who is on the other end of an MCP request: the label of the API key that
 * authenticated and what it is allowed to do. Bound into the container by the
 * auth middleware and read by the tools.
 */
class ContentMcpContext
{
    /** @param array<int, string> $abilities */
    public function __construct(
        public string $name = 'unknown',
        public array $abilities = [],
    ) {}

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities, true);
    }

    public function canWrite(): bool
    {
        return $this->can('write');
    }
}
