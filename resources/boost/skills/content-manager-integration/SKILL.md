---
name: content-manager-integration
description: Install, wire, extend and test a2zwebltd/laravel-content-manager in a host app (config/content-manager.php models/morph_map/tables/routes/views/events maps, BlogController/PageController, ContentSaved/ContentDeleted and on_change hooks, FeedableBlogPost, ContentUrls/ContentIndex, Nova resources, content:generate-drafts). Use when adding the package to an app, adopting an app that already has a blog, registering app-owned blog routes behind responsecache or markdown-response, subclassing a model or Nova resource, hooking sitemaps into content changes, or debugging missing tags, images or stale pages.
---

# Content manager integration

## When to use this skill

- Installing `a2zwebltd/laravel-content-manager`, or auditing an existing install.
- Adopting an app that already has `blog_posts` / `tags` / `pages` / `faqs` tables and `App\Models\BlogPost` and its siblings.
- Registering the blog routes yourself (response cache, markdown negotiation, a locale prefix).
- Subclassing a model or a Nova resource, pointing views at the app's own Blade files.
- Reacting to content changes (sitemap rebuilds, CDN purges), wiring the feed, sitemap or llms.txt.
- Writing content from code, or writing tests that create content.

For writing content through the MCP server, use the `content-manager-authoring` skill instead. Long form: `INTEGRATION.md` in the package root.

Namespace `A2ZWeb\ContentManager\`. Config `config/content-manager.php`, closure-free.

## Install / wiring checklist

Decide the case first. The cases differ in one dangerous place: existing data.

```bash
php artisan db:table blog_posts 2>&1 | head -3
ls app/Models | grep -iE 'blogpost|contentcategory|^tag|^page|^faq|^chunk'
```

**Greenfield (no tables, no models):**

1. `composer require a2zwebltd/laravel-content-manager`
2. `php artisan vendor:publish --tag=content-manager-config` then `php artisan migrate`
3. Fill in `editorial.brand`, `editorial.description` and `editorial.audience`. They feed `content://guidelines` and the AI draft prompt.
4. Set `layout` to the app's layout view, or keep `content-manager::layouts.app`.
5. Pick package-owned or app-owned routes (see Recipes).

**Adopting an existing blog.** Run these in order:

1. Keep the existing table names; set `tables.*` if they differ. The package's create-migrations are guarded with `Schema::hasTable()`: they record themselves and change nothing.
2. Delete the app's own models, Nova resources, controllers, factories and create-migrations for these tables. Leave their rows in `migrations`. Keep migrations that seed content.
3. Repoint references: `grep -rn 'App\\Models\\\(BlogPost\|ContentCategory\|Tag\|Page\|Faq\|Chunk\)' app config database tests routes resources`. Blade files count.
4. Check that `legacy_morph_types` lists the old class names, then `php artisan migrate`. `normalize_content_morph_types` rewrites them to aliases. Skip this and **tags come back empty and images disappear while every page still returns 200**.
5. Verify on a copy of production (`migrate --pretend` lies here):
   ```sql
   SELECT taggable_type, COUNT(*) FROM taggables GROUP BY 1;  -- only 'blog_post'
   SELECT model_type, COUNT(*) FROM media GROUP BY 1;         -- no App\Models\… rows
   ```
   If the backfill can't run: keep `class BlogPost extends \A2ZWeb\ContentManager\Models\BlogPost {}` in the app, point `models.blog_post` at it and set `morph_map => []`.

## API & config reference

**Maps** (`config/content-manager.php`):

| Key | Purpose |
|---|---|
| `models.{blog_post,content_category,tag,page,faq,chunk}` | Every relation, controller, factory, Nova resource and MCP tool resolves through `Support\Models::blogPost()` etc. Register subclasses here. |
| `morph_map` | `blog_post`, `content_page` aliases for `taggables` and `media` rows. `models.blog_post` and `morph_map.blog_post` must name the same class (e.g. both `FeedableBlogPost`). |
| `legacy_morph_types` | Old class name => alias, rewritten once by `normalize_content_morph_types`. |
| `tables.*` + `tables.prefix` | Rename any table. Migrations read the same values. |
| `routes.enabled`, `.prefix`, `.blog_prefix`, `.middleware`, `.names.*`, `.pages.slugs` | Package routes. `names.*` also drives `Support\Routes::url()`, the feed and `ContentUrls`, so keep it in sync with app-owned routes. |
| `views.{blog_index,blog_show,blog_category,blog_tag,blog_tags,page_show}`, `layout` | Point at the app's Blade files. |
| `events.enabled`, `.flush_response_cache`, `.on_change` | Content change events (below). |
| `chunks.cache_ttl` | Seconds; `0` disables. `Chunk::getByCode($code)` caches and forgets on save/delete. |
| `nova.register_resources`, `nova.group` | Nova auto-registration. |
| `mcp.*` | Remote MCP server; env `CONTENT_MCP_API_KEY` / `CONTENT_MCP_API_KEYS`. |
| `ai.*` | `content:generate-drafts` (needs `laravel/ai`). |

**View contract** (frozen within a major; the controllers' docblocks list it):

| View | Variables |
|---|---|
| `blog_index` | `posts` (paginator), `search`, `categories`, `tags`, `totalPosts` |
| `blog_show` | `post`, `related`, `previousPost`, `nextPost`, `categories`, `tags`, `totalPosts` |
| `blog_category` | `category`, `posts`, `categories`, `tags`, `totalPosts` |
| `blog_tag` | `tag`, `posts`, `categories`, `tags`, `totalPosts` |
| `blog_tags` | `groupedTags` (keyed by first letter) |
| `page_show` | `page` |

Render bodies with `$post->renderedContent()` / `$page->renderedContent()`; reading time is `$post->readingTime()`.

**Events.** `ContentSaved` / `ContentDeleted` (`public Model $model`, `type()` returns `blog_post`, `faq`, …) fire from Eloquent `created`/`updated`/`restored`/`deleted` on every package model, including MCP writes. They are gated by `events.enabled`. Listeners:
- `FlushResponseCache` calls `ResponseCache::clear()` when `spatie/laravel-responsecache` is installed and `flush_response_cache` is true. It does nothing when `APP_ENV=testing`.
- `RunContentChangeHooks` calls each class-string in `events.on_change` as `app($class)($event)`. It **does** run in testing.
- For changes Eloquent can't see (pivots, media), call `$model->announceContentChange()` (`FiresContentEvents`). It updates `updated_at` quietly and fires one `ContentSaved`.

**Sitemap / llms.txt / feed:**
- `Support\ContentUrls::all()` / `posts()` / `categories()` / `tags()` / `pages()` returns rows `['loc', 'lastmod', 'changefreq', 'priority', 'image' => ?['url', 'caption']]`. `collect()` wraps them in a Collection.
- `Support\ContentIndex::posts(bool $withContent = false, ?int $limit = null)`, `pages(bool $withContent = false)`, `faqs(?string $group = null)` return `title` / `url` / `description` rows (FAQs: `question` / `answer`).
- Feed (`spatie/laravel-feed`): in `config/feed.php` set `'items' => [\A2ZWeb\ContentManager\Feeds\FeedableBlogPost::class, 'getFeedItems']`, and set **both** `models.blog_post` and `morph_map.blog_post` to `FeedableBlogPost::class`.

**AI hooks** (`laravel/ai`; command `content:generate-drafts {--count=2} {--slug=}`): `ai.topic_provider` is a class-string implementing `A2ZWeb\ContentManager\Ai\TopicProvider::all()` (rows `slug`, `title_idea`, `keyword?`, `angle?`), or use the `ai.topics` array. `ai.before_call` is an invokable class-string called `($provider, $model, $topic)`. Drafts are created unpublished, and `ContentDraftGenerated` carries `promptTokens` / `completionTokens`. Set `CONTENT_AI_ENABLED=false` when the app meters AI elsewhere.

## Recipes

**App-owned routes** (`routes.enabled => false`). Params reach the controllers as strings:

```php
use A2ZWeb\ContentManager\Http\Controllers\BlogController;
use A2ZWeb\ContentManager\Http\Controllers\PageController;

Route::middleware(['markdownResponse', 'cacheResponse'])->group(function () {
    Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('blog/tags', [BlogController::class, 'tags'])->name('blog.tags');          // before {blogPost}
    Route::get('blog/tag/{tag}', [BlogController::class, 'tag'])->name('blog.tag');
    Route::get('blog/category/{contentCategory}', [BlogController::class, 'category'])->name('blog.category');
    Route::get('blog/{blogPost}', [BlogController::class, 'show'])->name('blog.show');
    Route::get('{page}', [PageController::class, 'show'])
        ->where('page', 'about|privacy|terms')                                            // whitelist, and last
        ->name('pages.show');
});
```

Share the page whitelist with anything else that validates page slugs (e.g. a Nova rule) through one constant, or the two lists drift apart.

**Response cache + markdown-response.** The package flushes responsecache on every change, but responsecache keys on host + path + method + user only. With `spatie/laravel-markdown-response` in the stack, the first client after a flush decides whether everyone gets HTML or Markdown. The cache key must encode the variant:
- A global middleware (`$middleware->append(...)`, before routing) sets `$request->attributes->set('responsecache.markdownVariant', …)` using the package detector `Config::getAction('detection.detector', DetectsMarkdownRequest::class)`.
- A cache profile extends `CacheAllSuccessfulGetRequests` and appends `:md` in `useCacheNameSuffix()`, wired in `config/responsecache.php` → `cache_profile`.
- Copy `/Volumes/Dane/workspace/brandgeo/app/Http/CacheProfiles/MarkdownAwareCacheProfile.php` and `app/Http/Middleware/FlagMarkdownVariant.php`. Reordering middleware doesn't fix it.
- markdown-response keeps its own URL-keyed cache (`markdown-response.cache`, TTL 3600) that content events don't clear. Disable it or give it a dedicated `store`. `markdown-response:clear` runs `clear()` on that store, which flushes the whole default cache if `store` is null. Deploys clear both `responsecache:clear` and `markdown-response:clear`.

**Sitemap hook that stays quiet in tests:**

```php
namespace App\Listeners;

use A2ZWeb\ContentManager\Events\ContentDeleted;
use A2ZWeb\ContentManager\Events\ContentSaved;

class RebuildSitemap
{
    public function __invoke(ContentSaved|ContentDeleted $event): void
    {
        if (app()->environment('testing')) {
            return; // otherwise every factory rebuilds the sitemap from an empty DB
        }

        \App\Jobs\GenerateSitemapJob::dispatch();
    }
}
// config/content-manager.php: 'events' => ['on_change' => [App\Listeners\RebuildSitemap::class]]
```

**Subclassing a model:**

```php
class BlogPost extends \A2ZWeb\ContentManager\Models\BlogPost
{
    public function author(): BelongsTo { return $this->belongsTo(User::class); }
}
// config: 'models' => ['blog_post' => App\Models\BlogPost::class, ...]
// Keep morph_map.blog_post as-is or point it at the same class. ResolvesMorphAlias keeps
// the alias 'blog_post' for parent and subclass alike.
```

**Nova subclassing.** Extend `A2ZWeb\ContentManager\Nova\BlogPost` (etc.) in `app/Nova`, call `parent::fields($request)` and add to it. Set `nova.register_resources => false` so the package doesn't register its own copy next to yours. Then register every resource you still want, e.g. `Nova::resources([\A2ZWeb\ContentManager\Nova\Chunk::class])`. Package resources resolve `newModel()` through `models.*`.

**Writing content from code.** Use per-record Eloquent: `Models::faq()::query()->where('group', 'home')->each(fn ($faq) => $faq->update(['answer' => …]))`, not `->update([...])` on the query. After a direct pivot or media write, call `$post->announceContentChange()`.

## Gotchas

- Query-builder `->update()` / `->delete()`, `DB::table()` and raw SQL skip `ContentSaved`/`ContentDeleted`: no cache flush, no `on_change`, and a stale `Chunk::getByCode()` for up to `chunks.cache_ttl`. Data migrations count too.
- `DB::table('taggables')->insert()` must write `'taggable_type' => 'blog_post'`.
- `/blog/tag/{tag:slug}` hands a model to a `string` parameter and breaks. Keep plain `{tag}`.
- A new CMS page 404s until its slug is added to `routes.pages.slugs` (or your app-owned whitelist).
- Renaming a variable in a host view without changing the controller breaks at render time only.
- `routes.names.*` must match app-owned route names. `Support\Routes::url()` returns `null` for an unknown name, so `ContentUrls` silently drops the row, `ContentIndex` drops the `url` key and the feed links to `url('/')`.
- Content models must never get a tenant/workspace global scope: public visitors have none.
- No `having()` without `GROUP BY` in content queries: it breaks on sqlite. Use `whereHas`, as the package does.

## Testing

- Factories: `BlogPost::factory()->published()|draft()|scheduled()|promoted()`, `Page::factory()->published()`, `Faq::factory()->published()`, `Chunk::factory()->unpublished()`, plus `Tag` and `ContentCategory`. They build `models.*`, so subclasses work.
- `Event::fake([ContentSaved::class, ContentDeleted::class])` to assert announcements, or `config()->set('content-manager.events.enabled', false)` to silence them.
- `on_change` hooks run in tests unless they return early on `app()->environment('testing')`.
- Guard the morph alias: create a post, attach a tag, and assert `DB::table('taggables')->value('taggable_type') === 'blog_post'`.
- MCP tools in tests: `ContentServer::tool(CreatePost::class, [...])->assertOk()`. With no `ContentMcpContext` bound the tools allow read and write. Bind `new ContentMcpContext('reader', ['read'])` to test a read-only key.

## Verification checklist

```bash
php artisan route:list --path=blog        # expected names; /blog/tags above /blog/{blogPost}
php artisan migrate --force               # on a copy of production
php artisan test
php artisan content:mcp-status            # registered or not, and each key's abilities
```

Then check: `/blog` hides drafts; a post shows its body, tags and image; `/blog/tag/{slug}` and `/blog/category/{slug}` filter; a CMS page is 200 and an unpublished one 404; `/feed` validates if wired; `/blog/x.md` and `/blog/x` return different content types after `responsecache:clear`; editing a post in Nova shows on the site right away.

## Troubleshooting

| Symptom | Cause |
|---|---|
| Tags empty, images gone, pages still 200 | Morph types still hold class names: run the normalisation, check `legacy_morph_types`. |
| Browsers get raw Markdown with `X-Robots-Tag: noindex` | Response-cache key doesn't encode the markdown variant (see Recipes). |
| An edit doesn't show on the site | Written via the query builder or raw SQL, a pivot/media write with no `announceContentChange()`, or a cache the package doesn't know (markdown-response, CDN). Hook `on_change`. |
| `.md` twin stale for about an hour | markdown-response's own cache. |
| `/mcp/content` 404 | No key configured, so the route isn't registered. `content:mcp-status`. |
| MCP 401 | Wrong `Authorization: Bearer` key. |
| `View [components.layouts.app] not found` | `layout` points at a view the app doesn't have. |
| Table already exists on migrate | An app create-migration was left in place. |
| Duplicate or clashing content resources in Nova | App subclasses registered while `nova.register_resources` is still true. |
