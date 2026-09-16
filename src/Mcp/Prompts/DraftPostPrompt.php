<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Prompts;

use A2ZWeb\ContentManager\Support\Editorial;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('draft-post')]
#[Description('A ready-made brief for writing a post in this site\'s voice: house guidelines plus the topic, keyword and angle you supply.')]
class DraftPostPrompt extends Prompt
{
    /** @return array<int, Argument> */
    public function arguments(): array
    {
        return [
            new Argument('topic', 'What the post is about.', required: true),
            new Argument('keyword', 'The primary search phrase to write around.'),
            new Argument('angle', 'The take or brief — what makes this post worth reading.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $topic = (string) $request->get('topic');
        $keyword = (string) $request->get('keyword', '');
        $angle = (string) $request->get('angle', '');

        $brief = collect([
            'TOPIC: '.$topic,
            $keyword === '' ? null : 'PRIMARY KEYWORD: '.$keyword,
            $angle === '' ? null : 'ANGLE: '.$angle,
        ])->filter()->implode("\n");

        return Response::text(
            Editorial::guidelines()
            ."\n\n## This assignment\n\n"
            .$brief
            ."\n\nWrite the post, then file it with create-post: markdown body in \"content\", and leave published_at empty so a human can review it."
        );
    }
}
