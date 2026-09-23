# Laravel Content Manager

[![Latest Version on Packagist](https://img.shields.io/packagist/v/a2zwebltd/laravel-content-manager.svg?style=flat-square)](https://packagist.org/packages/a2zwebltd/laravel-content-manager)
[![Total Downloads](https://img.shields.io/packagist/dt/a2zwebltd/laravel-content-manager.svg?style=flat-square)](https://packagist.org/packages/a2zwebltd/laravel-content-manager)
[![License](https://img.shields.io/packagist/l/a2zwebltd/laravel-content-manager.svg?style=flat-square)](LICENSE)

A portable content engine for Laravel: a markdown blog with categories and tags, database-backed CMS pages, FAQ entries, reusable content chunks, media-library images and Nova admin — plus a built-in **MCP server**, so AI agents can run the whole archive remotely with nothing but an API key.

Designed to drop into any Laravel app: every table name, model, route and view is overridable, so it fits an existing blog as readily as an empty project.

> **Integrating it into an app?** [INTEGRATION.md](INTEGRATION.md) is a step-by-step guide written for an AI coding agent. Point one at it —
> *"integrate a2zwebltd/laravel-content-manager into this app following its INTEGRATION.md"* — and it covers install, configuration, routes, views, adopting an app that already has a blog (including the morph-type backfill that silently drops every tag if it is skipped), the MCP server, and a verification checklist.
>
> Raw URL for an agent that needs to fetch it: `https://raw.githubusercontent.com/a2zwebltd/laravel-content-manager/main/INTEGRATION.md`
>
> **Connecting an agent to a site that already runs this package?** [MCP.md](MCP.md) is the usage reference: every tool and its arguments, the read-before-write conventions, the traps (soft-deleted slugs keep their URL, chunks render live, `force` is permanent) and what each error means.
> Raw URL: `https://raw.githubusercontent.com/a2zwebltd/laravel-content-manager/main/MCP.md`

## Requirements

- PHP 8.2+
- Laravel 11.45.3, 12.41.1 or 13
- `spatie/laravel-medialibrary` (required — featured images)
- `laravel/mcp` (required — the MCP server)
- Optional: `laravel/nova` (admin resources), `laravel/ai` (AI draft pipeline), `spatie/laravel-feed` (Atom/RSS), `spatie/laravel-responsecache` (auto-flushed on every content change)

## Installation

```bash
composer require a2zwebltd/laravel-content-manager
php artisan vendor:publish --tag=content-manager-config
php artisan migrate
```

That is the whole install: `/blog`, `/blog/tags`, `/blog/tag/{slug}`, `/blog/category/{slug}`, `/blog/{slug}` and the whitelisted page route are live, with the package's own views.

Publish the views to restyle them:

```bash
php artisan vendor:publish --tag=content-manager-views
```

## Features

- **Blog** — markdown posts with intro, subtitle, SEO meta, promotion flag, soft deletes, scheduled publishing, LIKE search, related posts (tags → categories → latest) and chronological neighbours.
- **Taxonomy** — categories and polymorphic tags, both with their own landing pages and descriptions.
- **Pages** — database-backed CMS pages whose route binding is scoped to published rows, so a draft 404s rather than leaking.
- **FAQ** — ordered entries, optionally grouped so each page renders its own set.
- **Chunks** — named snippets a template drops in by code, cached and invalidated on save.
- **Images** — media-library collections (`main`, `small`) with `full_size`, `preview` and `thumb` conversions; the Nova upload writes through the media library, so the admin and the front end agree on one file.
- **MCP server** — 22 tools, two resources and a prompt, behind a static API key.
- **AI drafts** — optional `content:generate-drafts` writes the backlog into unpublished posts for a human to review.
- **Sitemap and llms.txt helpers** — `ContentUrls` and `ContentIndex` hand the host plain arrays, so the package never has to depend on a sitemap package.

## The MCP server

Set a key and the endpoint appears; leave it unset and the route is never registered at all.

```env
CONTENT_MCP_API_KEY="<generate-a-long-random-string>"

# or several, each with its own abilities:
CONTENT_MCP_API_KEYS="writer:<key-one>:read|write,reader:<key-two>:read"
```

```bash
php artisan content:mcp-status     # is it registered, and what may each key do?
php artisan mcp:inspector mcp/content
```

Point an agent at it:

```bash
claude mcp add --transport http my-site-content https://example.com/mcp/content \
  --header "Authorization: Bearer <your-key>"
```

Full usage reference for a connected agent — arguments, workflows, conventions and error semantics: **[MCP.md](MCP.md)**.

### Tools

| Area | Tools |
|---|---|
| Posts | `list-posts`, `get-post`, `create-post`, `update-post`, `publish-post`, `unpublish-post`, `delete-post`, `restore-post`, `set-post-image` |
| Taxonomy | `list-categories`, `create-category`, `list-tags`, `create-tag` |
| Pages | `list-pages`, `get-page`, `upsert-page` |
| FAQ | `list-faqs`, `upsert-faq`, `delete-faq` |
| Chunks | `list-chunks`, `upsert-chunk` |
| Meta | `content-stats` |

Resources: `content://guidelines` (house style, built from your config) and `content://taxonomy` (the category and tag slugs that already exist). Prompt: `draft-post`.

A read-only key that calls a write tool gets a plain explanation back, not a crash. Every mutation is logged with the name of the key that made it.

## Routes

| Method | URI | Name |
|-|-|-|
| GET | `/blog` | `blog.index` |
| GET | `/blog/tags` | `blog.tags` |
| GET | `/blog/tag/{tag}` | `blog.tag` |
| GET | `/blog/category/{contentCategory}` | `blog.category` |
| GET | `/blog/{blogPost}` | `blog.show` |
| GET | `/{page}` (whitelisted slugs) | `pages.show` |
| POST | `/mcp/content` | — |

## Configuration

Everything lives in `config/content-manager.php`, which is closure-free and safe to `config:cache`.

- `models` — swap in your own subclass of any model; relations, controllers, Nova and the MCP tools all resolve through this map.
- `morph_map` / `legacy_morph_types` — tag pivots and media rows store the morph type as a string. The package aliases it (`blog_post`, `content_page`) and ships a migration that rewrites legacy class names, so adopting the package in an app that already had its own `App\Models\BlogPost` does not silently orphan every tag and image.
- `tables` — prefix or rename any table. `tags` and `pages` are the ones most likely to collide with something you already own.
- `routes` — set `enabled => false` to keep your own routes (and your own URLs, middleware and ordering) while still using the package's controllers.
- `views` — point any view at one of your own Blade files. The view data contract is frozen and covered by tests: `posts`, `search`, `categories`, `tags`, `totalPosts`, `post`, `related`, `previousPost`, `nextPost`, `tag`, `category`, `groupedTags`, `page`.
- `events` — `ContentSaved` / `ContentDeleted` fire on every change. The bundled listener clears the response cache; `on_change` takes invokable class-strings for anything else (rebuilding a sitemap, purging a CDN).
- `editorial` — the house style served to agents and used to build the AI prompt.
- `ai` — provider, model and the topic backlog for `content:generate-drafts`.

### Adopting it in an app that already has these tables

Full procedure: [INTEGRATION.md §6](INTEGRATION.md#6-case-b-only-adopting-an-app-that-already-has-this-content).

The migrations are guarded with `Schema::hasTable()`, so they record themselves and change nothing where the tables already exist. Delete your own create-migrations, keep their rows in the `migrations` table, and run the morph normalisation the package ships. Verify with:

```sql
SELECT taggable_type, COUNT(*) FROM taggables GROUP BY 1;   -- expect only the aliases
SELECT model_type, COUNT(*) FROM media GROUP BY 1;
```

## Feed

```php
// config/feed.php
'items' => [A2ZWeb\ContentManager\Feeds\FeedableBlogPost::class, 'getFeedItems'],
```

Then set `content-manager.models.blog_post` to the same class so the feed, the morph alias and Nova all agree on one model.

## AI agents (Laravel Boost)

The package ships [Laravel Boost](https://github.com/laravel/boost) resources: a short always-loaded guideline (`resources/boost/guidelines/core.blade.php`) with the rules that prevent silent breakage, and two on-demand skills. `content-manager-integration` covers wiring the package into an app. `content-manager-authoring` covers writing content over the `/mcp/content` server or through Eloquent. Boost 2 or newer is required.

In the host app:

```bash
composer require laravel/boost --dev
php artisan boost:install          # first time
php artisan boost:update --discover   # already using Boost
```

Select `a2zwebltd/laravel-content-manager` when Boost lists the packages it found.

## Testing

```bash
composer test
composer lint
```

---

## Security Vulnerabilities

Please report security issues to [contact@a2zweb.co](mailto:contact@a2zweb.co).

---

## License

MIT. See [LICENSE](LICENSE).

## Credits

- [A2Z WEB](https://a2zweb.co/)
- [Dawid Makowski](https://github.com/makowskid)
