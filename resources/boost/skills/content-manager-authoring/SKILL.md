---
name: content-manager-authoring
description: Write and edit a site's blog posts, CMS pages, FAQs and chunks managed by a2zwebltd/laravel-content-manager, either remotely through its HTTP MCP server (/mcp/content, bearer key, tools like create-post, update-post, publish-post, upsert-chunk, resources content://guidelines and content://taxonomy) or locally through Eloquent/tinker. Use when asked to draft, publish, update or delete site content, connect an agent to /mcp/content, run content:mcp-status, or when an agent confuses that server with Laravel Boost's MCP.
---

# Content manager authoring

## When to use this skill

- Drafting, updating, publishing or unpublishing a blog post, CMS page, FAQ entry or chunk.
- Connecting Claude Code (or another client) to a site's `/mcp/content` endpoint.
- Writing content locally through Eloquent or tinker instead of the MCP server.

For installing or wiring the package, use the `content-manager-integration` skill.

## Two different MCP servers

| | Laravel Boost | Content manager |
|---|---|---|
| Transport | local stdio, `php artisan boost:mcp` (in `.mcp.json`) | HTTP, `POST https://<site>/mcp/content` |
| Auth | none (local process) | `Authorization: Bearer <key>` (or `X-Content-Api-Key`) |
| Does | docs search, read-only `database-query`, `tinker`, logs | reads and writes the site's published content |
| Exists when | Boost is installed | a key is configured. With no key the route is absent (404, not 401) |

Boost's `database-query` is read-only; don't write content through `tinker` on production. Use the content server against a live site, and Eloquent locally.

## Install / wiring checklist

1. The operator sets `CONTENT_MCP_API_KEY="<long-random>"` or `CONTENT_MCP_API_KEYS="writer:<key>:read|write,reader:<key>:read"` in `.env`, then `php artisan config:clear`.
2. `php artisan content:mcp-status` shows whether the endpoint is registered and what each key may do.
3. Connect, keeping the key out of the repository (never commit it to `.mcp.json`):
   ```bash
   claude mcp add --transport http <site>-content https://<site>/mcp/content \
     --header "Authorization: Bearer $CONTENT_KEY"
   ```
   The default local scope keeps it in your user config, not the project. Use different keys for local and production.
4. Smoke test: call the `content-stats` tool. `tools/list` is paginated, so seeing 15 of 22 tools at first is normal.

## API & config reference

Start every session with:
1. Read `content://guidelines`: house voice, audience, rules and character limits (`editorial.*` in config).
2. Read `content://taxonomy`: existing category and tag slugs with post counts.
3. Call `content-stats` for archive size and the latest post.

Tools (22): posts `list-posts`, `get-post`, `create-post`, `update-post`, `publish-post`, `unpublish-post`, `delete-post`, `restore-post`, `set-post-image`; taxonomy `list-categories`, `create-category`, `list-tags`, `create-tag`; pages `list-pages`, `get-page`, `upsert-page`; FAQ `list-faqs`, `upsert-faq`, `delete-faq`; chunks `list-chunks`, `upsert-chunk`; `content-stats`. Prompt `draft-post` (`topic`*, `keyword`, `angle`).

Arguments, response shapes and error messages: [references/mcp-tools.md](references/mcp-tools.md).

## Recipes

**A post, end to end (MCP):**

```
1. read content://guidelines          → voice, rules, field limits
2. read content://taxonomy            → existing categories and tags
3. list-posts search:"<topic>"        → already covered?
4. create-post                        → title, markdown content, intro, meta_*, category_slugs, tag_slugs; NO published_at
5. set-post-image                     → optional, url (http/https) or base64
6. get-post slug:"…"                  → read back what was stored
7. publish-post                       → only when a human asked; a future published_at schedules it
```

Body: GitHub-flavoured markdown with `##`/`###` headings, lists, tables, fenced code. Never repeat the title as an H1. `intro` is the listing teaser; `meta_title` and `meta_description` are the SERP snippet.

**Local authoring (Eloquent / tinker)**, following the same rules as the host code:

```php
use A2ZWeb\ContentManager\Support\Models;

$post = Models::blogPost()::query()->create([
    'title' => 'How AI engines pick sources',
    'content' => "## First heading\n\nBody…",
    'published_at' => null,                  // draft
]);
$post->categories()->sync(Models::contentCategory()::query()->whereIn('slug', ['seo'])->pluck('id'));
$post->tags()->sync([$tagId]);
$post->announceContentChange();              // pivots don't fire ContentSaved on their own
```

One record, one Eloquent `save()`/`create()`/`update()`/`delete()` at a time. Query-builder `->update()`, `DB::table()` and raw SQL skip the cache flush, the `on_change` hooks and the chunk cache. Morph columns take `blog_post` / `content_page`, never class names.

## Gotchas

- **Changes are live.** Publishing puts a post on a public site. A chunk edit shows up on every page that renders it immediately, and a new chunk is published by default.
- **`upsert-faq` publishes a new entry immediately** unless you pass `publish: false`. Posts and pages start as drafts.
- **Soft-deleted posts keep their slug.** `create-post` with that slug is refused ("already taken by post #N (soft-deleted)"). Use `restore-post` or another slug.
- **`delete-post` with `force: true` is permanent.** So is `delete-faq`: FAQs are hard-deleted.
- **Categories are only created on request.** Unknown `category_slugs` come back in `unknown_categories` and are not created. Tags are created on the fly. Ask a human before `create-category`.
- **`category_slugs` / `tag_slugs` replace** the current set on `update-post`. Pass the full list.
- **`new_slug` changes the URL**, so old links break. Only on explicit request.
- **Pages are usually legal text.** Edit privacy or terms wording only on explicit instruction. A new page slug also needs the host's route whitelist.
- **`is_promoted`** is the site owner's call.
- Never fabricate facts, statistics, quotes or testimonials. Every write is logged with the key's name.

## Testing

- `php artisan content:mcp-status` for the endpoint state. For HTTP errors: 404 means no key configured, 401 means a wrong key, 429 means rate limited (`CONTENT_MCP_THROTTLE`, default `60,1`).
- Smoke test with curl:
  ```bash
  curl -s -X POST https://<site>/mcp/content -H 'Content-Type: application/json' \
    -H 'Accept: application/json, text/event-stream' -H "Authorization: Bearer $CONTENT_KEY" \
    -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"content-stats","arguments":{}}}'
  ```
- After a write, `get-post` / `get-page` to read back what was stored, and load the public URL.
