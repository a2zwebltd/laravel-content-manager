<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Mcp;

use A2ZWeb\ContentManager\Mcp\Prompts\DraftPostPrompt;
use A2ZWeb\ContentManager\Mcp\Resources\GuidelinesResource;
use A2ZWeb\ContentManager\Mcp\Resources\TaxonomyResource;
use A2ZWeb\ContentManager\Mcp\Tools\ContentStats;
use A2ZWeb\ContentManager\Mcp\Tools\CreateCategory;
use A2ZWeb\ContentManager\Mcp\Tools\CreatePost;
use A2ZWeb\ContentManager\Mcp\Tools\CreateTag;
use A2ZWeb\ContentManager\Mcp\Tools\DeleteFaq;
use A2ZWeb\ContentManager\Mcp\Tools\DeletePost;
use A2ZWeb\ContentManager\Mcp\Tools\GetPage;
use A2ZWeb\ContentManager\Mcp\Tools\GetPost;
use A2ZWeb\ContentManager\Mcp\Tools\ListCategories;
use A2ZWeb\ContentManager\Mcp\Tools\ListChunks;
use A2ZWeb\ContentManager\Mcp\Tools\ListFaqs;
use A2ZWeb\ContentManager\Mcp\Tools\ListPages;
use A2ZWeb\ContentManager\Mcp\Tools\ListPosts;
use A2ZWeb\ContentManager\Mcp\Tools\ListTags;
use A2ZWeb\ContentManager\Mcp\Tools\PublishPost;
use A2ZWeb\ContentManager\Mcp\Tools\RestorePost;
use A2ZWeb\ContentManager\Mcp\Tools\SetPostImage;
use A2ZWeb\ContentManager\Mcp\Tools\UnpublishPost;
use A2ZWeb\ContentManager\Mcp\Tools\UpdatePost;
use A2ZWeb\ContentManager\Mcp\Tools\UpsertChunk;
use A2ZWeb\ContentManager\Mcp\Tools\UpsertFaq;
use A2ZWeb\ContentManager\Mcp\Tools\UpsertPage;
use Laravel\Mcp\Server;

/**
 * The content-management MCP server: everything an agent needs to run this
 * site's blog, pages, FAQ and chunks over HTTP, with no access to anything
 * else in the application.
 */
class ContentServer extends Server
{
    protected string $name = 'Content Manager';

    protected string $version = '1.0.0';

    /** @var array<int, class-string> */
    protected array $tools = [
        ListPosts::class,
        GetPost::class,
        CreatePost::class,
        UpdatePost::class,
        PublishPost::class,
        UnpublishPost::class,
        DeletePost::class,
        RestorePost::class,
        SetPostImage::class,
        ListCategories::class,
        CreateCategory::class,
        ListTags::class,
        CreateTag::class,
        ListPages::class,
        GetPage::class,
        UpsertPage::class,
        ListFaqs::class,
        UpsertFaq::class,
        DeleteFaq::class,
        ListChunks::class,
        UpsertChunk::class,
        ContentStats::class,
    ];

    /** @var array<int, class-string> */
    protected array $resources = [
        GuidelinesResource::class,
        TaxonomyResource::class,
    ];

    /** @var array<int, class-string> */
    protected array $prompts = [
        DraftPostPrompt::class,
    ];

    protected function boot(): void
    {
        $this->instructions = (string) config(
            'content-manager.mcp.instructions',
            $this->instructions,
        );

        if (is_string($name = config('content-manager.mcp.server_name')) && $name !== '') {
            $this->name = $name;
        }
    }
}
