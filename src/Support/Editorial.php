<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Support;

/**
 * Turns the editorial config into the brief that both the AI draft prompt and
 * the content://guidelines MCP resource hand to a writer, so a remote agent
 * writes in the same voice as the local pipeline.
 */
class Editorial
{
    public function __invoke(): string
    {
        return self::guidelines();
    }

    public static function guidelines(): string
    {
        $config = (array) config('content-manager.editorial', []);
        $brand = (string) ($config['brand'] ?? config('app.name'));
        $limits = (array) ($config['limits'] ?? []);

        $lines = ['# Editorial guidelines for '.$brand, ''];

        if (filled($config['description'] ?? null)) {
            $lines[] = '## What this site is';
            $lines[] = (string) $config['description'];
            $lines[] = '';
        }

        if (filled($config['audience'] ?? null)) {
            $lines[] = '## Who reads it';
            $lines[] = (string) $config['audience'];
            $lines[] = '';
        }

        $rules = (array) ($config['rules'] ?? []);

        if ($rules !== []) {
            $lines[] = '## Rules';

            foreach ($rules as $rule) {
                $lines[] = '- '.$rule;
            }

            $lines[] = '';
        }

        if (filled($config['spelling'] ?? null)) {
            $lines[] = 'Spelling: '.$config['spelling'].'.';
            $lines[] = '';
        }

        if ($limits !== []) {
            $lines[] = '## Field limits (characters)';

            foreach ($limits as $field => $limit) {
                $lines[] = sprintf('- %s: %d', str_replace('_', ' ', (string) $field), (int) $limit);
            }

            $lines[] = '';
        }

        $lines[] = 'Bodies are stored as markdown and rendered with GitHub-flavoured markdown: headings, lists, tables and fenced code all work.';

        return implode("\n", $lines);
    }

    /** @return array<string, mixed> */
    public static function limits(): array
    {
        return (array) config('content-manager.editorial.limits', []);
    }
}
