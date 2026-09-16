<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp\Resources;

use A2ZWeb\ContentManager\Support\Editorial;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('editorial-guidelines')]
#[Uri('content://guidelines')]
#[MimeType('text/markdown')]
#[Description('House style for this site: who it is written for, the rules a post has to follow, and the length limits for titles and meta fields. Read this before writing.')]
class GuidelinesResource extends Resource
{
    public function handle(Request $request): Response
    {
        return Response::text(Editorial::guidelines());
    }
}
