<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\ContentManagerServiceProvider;
use A2ZWeb\ContentManager\Mcp\ContentMcpContext;
use A2ZWeb\ContentManager\Mcp\ContentServer;
use A2ZWeb\ContentManager\Mcp\Tools\CreatePost;
use A2ZWeb\ContentManager\Mcp\Tools\ListPosts;
use A2ZWeb\ContentManager\Models\BlogPost;

function jsonRpc(array $overrides = []): array
{
    return array_merge(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], $overrides);
}

it('rejects a request with no key', function (): void {
    $this->postJson('/mcp/content', jsonRpc())->assertUnauthorized();
});

it('rejects a wrong key', function (): void {
    $this->withToken('not-the-key')
        ->postJson('/mcp/content', jsonRpc())
        ->assertUnauthorized();
});

it('lets a valid bearer key through', function (): void {
    $this->withToken('test-write-key')
        ->postJson('/mcp/content', jsonRpc())
        ->assertOk();
});

it('accepts the key in the X-Content-Api-Key header too', function (): void {
    $this->withHeader('X-Content-Api-Key', 'test-read-key')
        ->postJson('/mcp/content', jsonRpc())
        ->assertOk();
});

it('advertises bearer auth when it refuses', function (): void {
    $response = $this->postJson('/mcp/content', jsonRpc())->assertUnauthorized();

    expect($response->headers->get('WWW-Authenticate'))->toStartWith('Bearer');
});

it('says why it refused', function (): void {
    $this->postJson('/mcp/content', jsonRpc())
        ->assertUnauthorized()
        ->assertSee('API key');
});

it('lets a read-only key read', function (): void {
    BlogPost::factory()->published()->create(['title' => 'Readable']);

    app()->instance(ContentMcpContext::class, new ContentMcpContext('reader', ['read']));

    ContentServer::tool(ListPosts::class, [])
        ->assertOk()
        ->assertSee('Readable');
});

it('stops a read-only key from writing, with an explanation rather than a crash', function (): void {
    app()->instance(ContentMcpContext::class, new ContentMcpContext('reader', ['read']));

    ContentServer::tool(CreatePost::class, ['title' => 'Nope', 'content' => 'Body'])
        ->assertHasErrors()
        ->assertSee('read-only');

    expect(BlogPost::query()->count())->toBe(0);
});

it('treats an empty key list as "no endpoint", not "open endpoint"', function (): void {
    config()->set('content-manager.mcp.keys', []);

    expect(ContentManagerServiceProvider::mcpIsAvailable())->toBeFalse();
});
