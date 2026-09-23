# Integration guide

This file is written for an AI coding agent. Point one at it — "integrate
a2zwebltd/laravel-content-manager into this app following its INTEGRATION.md"
— and work through it top to bottom. A human reading it gets the same
instructions; nothing here is specific to any one application.

Raw URL, for an agent that needs to fetch it:
`https://raw.githubusercontent.com/a2zwebltd/laravel-content-manager/main/INTEGRATION.md`

---

## 0. Decide which case you are in

**Read this before touching anything.** The two cases differ in exactly one
dangerous place: existing data.

| | Case A — greenfield | Case B — the app already has a blog |
|---|---|---|
| The app has `blog_posts` / `tags` / `pages` / `faqs` tables | no | yes |
| Work needed | install, configure, done | install, then migrate schema ownership and morph types, then delete the app's own copies |
| Main risk | none | tag pivots and media rows silently stop resolving |

Find out which with:

```bash
php artisan db:table blog_posts 2>&1 | head -3
ls app/Models | grep -iE 'blogpost|contentcategory|^tag|^page|^faq|^chunk'
```

If Case B, read §6 in full before running any migration.

---

## 1. Install

```bash
composer require a2zwebltd/laravel-content-manager
php artisan vendor:publish --tag=content-manager-config
php artisan migrate
```

Requirements: PHP 8.2+, Laravel 11.45.3 / 12.41.1 / 13. `spatie/laravel-medialibrary`
and `laravel/mcp` come with the package.

If `laravel/mcp` conflicts with something already installed (for example an old
`laravel/boost`), upgrade that package rather than downgrading this one:

```bash
composer why-not laravel/mcp 1.0
```

Verify the install before going further:

```bash
php artisan config:show content-manager   # the package's config resolves
php artisan route:list --path=blog        # 5 routes, unless you disable them in §3
```

## 2. Configure the essentials

Everything lives in `config/content-manager.php`. It is closure-free, so it
survives `config:cache`. The keys that matter on day one:

```php
'layout' => 'components.layouts.app',   // your app's layout, or leave the package's own
'editorial' => [
    'brand' => 'Acme',
    'description' => 'One paragraph: what this site is and sells.',
    'audience' => 'Who reads it.',
    // 'rules' are the house style; they are served to AI agents verbatim.
],
```

`editorial` is not decoration. It is what the `content://guidelines` MCP
resource returns and what the AI draft prompt is built from, so an agent writing
a post for this app writes in this app's voice. Fill it in properly.

## 3. Routes: package-owned or app-owned

**Default (package-owned).** Leave `routes.enabled => true`. The package
registers `/blog`, `/blog/tags`, `/blog/tag/{tag}`, `/blog/category/{category}`,
`/blog/{post}` and a whitelisted `/{page}`. Adjust `routes.prefix`,
`routes.blog_prefix`, `routes.middleware` and `routes.pages.slugs` to taste.

**App-owned.** Choose this when the app needs its own middleware, URLs or route
ordering — a response cache, a markdown negotiator, a locale prefix. Set
`routes.enabled => false` and register them yourself, pointing at the package's
controllers:

```php
use A2ZWeb\ContentManager\Http\Controllers\BlogController;
use A2ZWeb\ContentManager\Http\Controllers\PageController;

Route::middleware(['web', 'your-middleware'])->group(function () {
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/tags', [BlogController::class, 'tags'])->name('blog.tags');       // before {post}
    Route::get('/blog/tag/{tag}', [BlogController::class, 'tag'])->name('blog.tag');
    Route::get('/blog/category/{contentCategory}', [BlogController::class, 'category'])->name('blog.category');
    Route::get('/blog/{blogPost}', [BlogController::class, 'show'])->name('blog.show');
    Route::get('/{page}', [PageController::class, 'show'])->where('page', 'about|privacy|terms')->name('pages.show');
});
```

Two rules whichever you pick: `/blog/tags` must be declared **before**
`/blog/{blogPost}`, and the page catch-all must come **last**. If you change any
route name, change `routes.names.*` to match — link building, the feed and the
sitemap helpers all read it.

## 4. Views

The package ships plain, Flux-free views that work immediately. To use the
app's own design instead, either publish and edit them:

```bash
php artisan vendor:publish --tag=content-manager-views   # → resources/views/vendor/content-manager
```

…or point the view map at Blade files the app already has:

```php
'views' => [
    'blog_index' => 'blog.index',
    'blog_show' => 'blog.show',
    'blog_category' => 'blog.category',
    'blog_tag' => 'blog.tag',
    'blog_tags' => 'blog.tags',
    'page_show' => 'pages.show',
],
```

Those views receive exactly these variables — this contract is covered by tests
and will not change within a major version:

| View | Variables |
|---|---|
| `blog_index` | `posts` (paginator), `search`, `categories`, `tags`, `totalPosts` |
| `blog_show` | `post`, `related`, `previousPost`, `nextPost`, `categories`, `tags`, `totalPosts` |
| `blog_category` | `category`, `posts`, `categories`, `tags`, `totalPosts` |
| `blog_tag` | `tag`, `posts`, `categories`, `tags`, `totalPosts` |
| `blog_tags` | `groupedTags` (keyed by first letter) |
| `page_show` | `page` |

Render bodies with `$post->renderedContent()` / `$page->renderedContent()` and
show reading time with `$post->readingTime()`; both go through the package's
markdown helper so the site, the feed and any PDF agree.

## 5. Optional integrations

**Nova.** Resources register themselves when Nova is installed. Set
`nova.group` for the menu section, or `nova.register_resources => false` to opt
out. If the app's Nova menu is built by hand, the resources answer
`$resource::group()` like any other.

**Feed** (`spatie/laravel-feed`):

```php
// config/feed.php
'items' => [A2ZWeb\ContentManager\Feeds\FeedableBlogPost::class, 'getFeedItems'],
```

…and set `content-manager.models.blog_post` **and** `morph_map.blog_post` to
that same class, so the feed, the tag pivots and Nova all agree on one model.

**Response cache.** Flushed automatically when `spatie/laravel-responsecache`
is installed. Turn it off with `events.flush_response_cache => false`.

**Sitemap / llms.txt.** `A2ZWeb\ContentManager\Support\ContentUrls` returns
plain arrays (`loc`, `lastmod`, `changefreq`, `priority`, `image`) and
`ContentIndex` returns `title` / `url` / `description` rows, so the app can feed
whatever sitemap builder it already uses without the package depending on one.

**Anything else on change.** `ContentSaved` and `ContentDeleted` fire for every
content type. Listen to them, or list invokable class-strings in
`events.on_change` (class-strings, not closures, so the config still caches):

```php
'on_change' => [App\Support\RebuildSitemap::class],
```

Events come from Eloquent model events, so they fire only for per-record
`save()` / `create()` / `update()` / `delete()`. Query-builder `->update()`,
`DB::table()` and raw SQL announce nothing. Pivot and media writes
(`$post->tags()->sync()`, `addMedia…()`) don't dirty the post either, so follow
them with `$post->announceContentChange()`. The MCP tools and the Nova
featured-image upload and delete already do. A Nova subclass that replaces the
image field must keep that: return `fn () => $model->announceContentChange()`
from its `store()` callback, since Nova runs a returned closure after the save.

**AI drafts** (needs `laravel/ai`). Set `ai.provider` / `ai.model`, then either
fill `ai.topics` or point `ai.topic_provider` at a class implementing
`A2ZWeb\ContentManager\Ai\TopicProvider`. Run `php artisan content:generate-drafts`.
Drafts are always created unpublished. `ContentDraftGenerated` carries the token
counts if the app does its own cost accounting.

## 6. Case B only: adopting an app that already has this content

Do these in order. Step 3 is the one that breaks production if skipped.

**6.1 Keep the existing table names.** The defaults already match the usual
Laravel naming. If the app's tables differ, set `tables.*` rather than renaming
anything. The package's create-migrations are guarded with `Schema::hasTable()`,
so they record themselves and change nothing where a table already exists.

**6.2 Delete the app's own copies.** Models, Nova resources, controllers,
factories, and the create-migrations for these tables. Leave the migration rows
in the `migrations` table — an orphan row is inert, and deleting the files is
what stops a fresh install from creating the tables twice. Keep any migration
that seeds *content*: that is the app's editorial data, not schema.

Then repoint every reference:

```bash
grep -rn 'App\\Models\\\(BlogPost\|ContentCategory\|Tag\|Page\|Faq\|Chunk\)' app config database tests routes resources
```

Blade files count — a view calling `\App\Models\Faq::query()` fails only when
that page is rendered.

**6.3 Fix the morph types. This is the dangerous one.**

Tag pivots (`taggables.taggable_type`) and media rows (`media.model_type`) store
the model as a literal string, usually `App\Models\BlogPost`. Once that class is
gone, those rows resolve to nothing: **tags come back empty and images
disappear, while every page still returns 200.** No error, no log line.

The package aliases morph types (`blog_post`, `content_page`) and ships
`normalize_content_morph_types`, which rewrites the legacy names listed in
`legacy_morph_types`. Check that list covers the app's old class names, then:

```bash
php artisan migrate
```

Verify against real data — do not skip this:

```sql
SELECT taggable_type, COUNT(*) FROM taggables GROUP BY 1;  -- expect only 'blog_post'
SELECT model_type, COUNT(*) FROM media GROUP BY 1;         -- expect no App\Models\… rows
```

Note that `migrate --pretend` is misleading here: in pretend mode the guard's
`SELECT` returns nothing, so it reports creating tables that plainly exist. Test
against a copy of the production database instead.

**Escape hatch.** If the backfill cannot be run, keep a subclass in the app
(`class BlogPost extends A2ZWeb\ContentManager\Models\BlogPost {}`), point
`models.blog_post` at it and set `morph_map => []`. The stored class names keep
resolving and no row changes.

**6.4 Any raw insert must write the alias.** A seeder or migration using
`DB::table('taggables')->insert([...])` has to write `'taggable_type' => 'blog_post'`,
not a class name. `DB::table` bypasses Eloquent, so nothing converts it for you.

**6.5 Re-run the app's own test suite.** Every failure at this point is a
reference the grep in 6.2 missed.

## 7. The MCP server

This is what lets an AI agent manage the content of this app remotely.

```env
CONTENT_MCP_API_KEY="generate-a-long-random-string"

# or several keys, each with its own abilities:
CONTENT_MCP_API_KEYS="writer:key-one:read|write,reader:key-two:read"
```

```bash
php artisan config:clear
php artisan content:mcp-status
```

**With no key configured the route is never registered** — a 404, not a 401.
That is deliberate: a content-management endpoint should not exist unless
someone switched it on. `content:mcp-status` will tell you which state you are
in.

Once it is live, an agent connecting to it should read
[MCP.md](MCP.md) — the tool reference, conventions and error semantics for
using the server.

Connect a client:

```bash
claude mcp add --transport http <app-name>-content https://example.com/mcp/content \
  --header "Authorization: Bearer <key>"
```

Smoke-test it from the command line:

```bash
curl -s -X POST https://example.com/mcp/content \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  -H 'Authorization: Bearer <key>' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"content-stats","arguments":{}}}'
```

Notes worth knowing: `tools/list` is paginated, so a client showing 15 of the 22
tools is behaving correctly. A read-only key that calls a write tool gets a
readable error, not a 500. Every mutation is logged with the name of the key
that made it. Put the endpoint behind HTTPS and treat the key like a password —
it grants full control of the site's published content.

## 8. Verification checklist

Work through all of it before calling the integration done.

```bash
php artisan route:list --path=blog        # expected URLs and names, /blog/tags before /blog/{post}
php artisan migrate --force               # no errors on a copy of production
php artisan test                          # the app's own suite, green
php artisan content:mcp-status            # registered, with the abilities you intended
```

Then, in a browser or with curl:

- `/blog` lists published posts and hides drafts
- a post page renders its body, its tags, and its image if it has one
- `/blog/tag/{slug}` and `/blog/category/{slug}` filter correctly — **if tags
  render empty here on an app from Case B, go back to §6.3**
- a CMS page returns 200, and an unpublished one returns 404
- `/feed` validates, if the feed is wired
- the admin can create a post, upload a featured image, and see it on the front end

## 9. Troubleshooting

| Symptom | Cause |
|---|---|
| Tags empty, images gone, pages still 200 | Morph types never normalised — §6.3 |
| `Class "Database\Factories\…Factory" not found` | A host subclass without `newFactory()` |
| MCP endpoint 404s | No key configured, so the route was never registered — §7 |
| MCP returns 401 | Key mismatch; check `Authorization: Bearer` and `content:mcp-status` |
| Write tools refuse | That key has `read` only |
| Edits do not show on the site | A query-builder or raw-SQL write, a pivot/media write without `announceContentChange()`, or a cache the package does not know about; hook `events.on_change` |
| `View [components.layouts.app] not found` | `layout` points at a view this app does not have |
| Table already exists on migrate | An app create-migration was left in place — §6.2 |
| Chunk edits take an hour to appear | A cached lookup outside the package; the package's own cache is invalidated on save |
