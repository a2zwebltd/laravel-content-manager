<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Symfony\Component\Yaml\Yaml;

function boostPath(string $path = ''): string
{
    return dirname(__DIR__, 2).'/resources/boost'.($path !== '' ? '/'.$path : '');
}

/** @return array<string, mixed> */
function boostFrontmatter(string $contents): array
{
    if (! preg_match('/\A---\R(.*?)\R---\R/s', $contents, $matches)) {
        return [];
    }

    if (class_exists(Yaml::class)) {
        return (array) Yaml::parse($matches[1]);
    }

    $data = [];

    foreach (preg_split('/\R/', $matches[1]) ?: [] as $line) {
        if (preg_match('/^([a-z_-]+):\s*(.*)$/i', $line, $pair)) {
            $data[$pair[1]] = trim($pair[2]);
        }
    }

    return $data;
}

it('ships the boost guideline, skills and references', function (): void {
    expect(boostPath('guidelines/core.blade.php'))->toBeFile()
        ->and(boostPath('skills/content-manager-integration/SKILL.md'))->toBeFile()
        ->and(boostPath('skills/content-manager-authoring/SKILL.md'))->toBeFile()
        ->and(boostPath('skills/content-manager-authoring/references/mcp-tools.md'))->toBeFile();
});

it('gives every skill frontmatter whose name matches its folder', function (): void {
    $skills = glob(boostPath('skills/*/SKILL.md')) ?: [];

    expect($skills)->toHaveCount(2);

    foreach ($skills as $skill) {
        $frontmatter = boostFrontmatter((string) file_get_contents($skill));

        expect($frontmatter['name'] ?? null)->toBe(basename(dirname($skill)))
            ->and(trim((string) ($frontmatter['description'] ?? '')))->not->toBe('');
    }
});

it('renders the guideline through Blade', function (): void {
    $html = Blade::render((string) file_get_contents(boostPath('guidelines/core.blade.php')));

    expect($html)->toContain('## Laravel Content Manager')
        ->and($html)->toContain('content-manager-integration')
        ->and($html)->toContain('content-manager-authoring');
});

it('keeps the boost resources in the distributed archive', function (): void {
    $root = dirname(__DIR__, 2);
    $attributes = is_file($root.'/.gitattributes') ? (string) file_get_contents($root.'/.gitattributes') : '';
    $composer = json_decode((string) file_get_contents($root.'/composer.json'), true);

    expect($attributes)->not->toMatch('#^/?resources\S*\s+export-ignore#m')
        ->and($composer['archive']['exclude'] ?? [])->not->toContain('resources')
        ->and($composer['archive']['exclude'] ?? [])->not->toContain('/resources');
});
