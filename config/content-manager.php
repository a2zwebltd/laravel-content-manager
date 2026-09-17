<?php

use A2ZWeb\ContentManager\Models\BlogPost;
use A2ZWeb\ContentManager\Models\Chunk;
use A2ZWeb\ContentManager\Models\ContentCategory;
use A2ZWeb\ContentManager\Models\Faq;
use A2ZWeb\ContentManager\Models\Page;
use A2ZWeb\ContentManager\Models\Tag;

return [

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Every relation, controller, Nova resource and MCP tool resolves its model
    | through this map, so a host may subclass any of them (to add an author
    | relation, say) and register the subclass here without touching the
    | package. Keys are also the morph aliases below.
    |
    */

    'models' => [
        'blog_post' => BlogPost::class,
        'content_category' => ContentCategory::class,
        'tag' => Tag::class,
        'page' => Page::class,
        'faq' => Faq::class,
        'chunk' => Chunk::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Morph map
    |--------------------------------------------------------------------------
    |
    | Tag pivots and media rows store the morph type as a string. Aliasing it
    | keeps those rows valid no matter which class ends up handling them —
    | without this, moving a model between namespaces silently orphans every
    | tag and every image. `legacy_morph_types` lists class names that older
    | rows may still carry; the bundled normalize migration rewrites them.
    |
    */

    'morph_map' => [
        'blog_post' => BlogPost::class,
        'content_page' => Page::class,
    ],

    'legacy_morph_types' => [
        'App\Models\BlogPost' => 'blog_post',
        'App\Models\Page' => 'content_page',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | `tags` and `pages` are common names — set a prefix or rename individual
    | tables when they would collide with something the host already owns. The
    | bundled migrations read these same values, so schema and models agree.
    |
    */

    'tables' => [
        'prefix' => '',
        'blog_posts' => 'blog_posts',
        'content_categories' => 'content_categories',
        'blog_post_content_category' => 'blog_post_content_category',
        'tags' => 'tags',
        'taggables' => 'taggables',
        'pages' => 'pages',
        'faqs' => 'faqs',
        'chunks' => 'chunks',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Disable these when the host owns its own blog routes (it keeps full
    | control of URLs, middleware and ordering, and simply points them at this
    | package's controllers). `names` is what ContentUrls and the feed use to
    | build links, so keep it in sync with whoever registers the routes.
    |
    */

    'routes' => [
        'enabled' => true,
        'prefix' => '',
        'name_prefix' => '',
        'middleware' => ['web'],
        'blog_prefix' => 'blog',
        'names' => [
            'index' => 'blog.index',
            'show' => 'blog.show',
            'category' => 'blog.category',
            'tag' => 'blog.tag',
            'tags' => 'blog.tags',
            'page' => 'pages.show',
        ],
        'pages' => [
            'enabled' => true,
            // Whitelisted so a CMS page can never shadow a future top-level route.
            'slugs' => ['about', 'privacy', 'terms'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | Point these at the host's own Blade files to keep a branded front end
    | while still using the package's controllers. The view data contract is
    | documented in the README and frozen by tests.
    |
    */

    'layout' => env('CONTENT_MANAGER_LAYOUT', 'content-manager::layouts.app'),

    'views' => [
        'blog_index' => 'content-manager::blog.index',
        'blog_show' => 'content-manager::blog.show',
        'blog_category' => 'content-manager::blog.category',
        'blog_tag' => 'content-manager::blog.tag',
        'blog_tags' => 'content-manager::blog.tags',
        'page_show' => 'content-manager::pages.show',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blog behaviour
    |--------------------------------------------------------------------------
    */

    'blog' => [
        'per_page' => 12,
        'related_limit' => 3,
        'words_per_minute' => 220,
    ],

    'media' => [
        'disk' => env('CONTENT_MANAGER_MEDIA_DISK', 'public'),
        'collection' => 'main',
    ],

    'chunks' => [
        // Seconds. 0 disables caching entirely; cached values are forgotten on save.
        'cache_ttl' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content change events
    |--------------------------------------------------------------------------
    |
    | Every create/update/delete fires ContentSaved / ContentDeleted. The
    | bundled listener clears spatie/laravel-responsecache when installed;
    | `on_change` takes invokable class-strings (no closures, so the config
    | still caches) for anything else the host wants to run — rebuilding a
    | sitemap, warming a CDN, pinging a search engine.
    |
    */

    'events' => [
        'enabled' => true,
        'flush_response_cache' => true,
        'on_change' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feed
    |--------------------------------------------------------------------------
    */

    'feed' => [
        'author' => env('CONTENT_MANAGER_FEED_AUTHOR', env('APP_NAME', 'Laravel')),
        'limit' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Nova
    |--------------------------------------------------------------------------
    */

    'nova' => [
        'register_resources' => true,
        'group' => 'Content',
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP server
    |--------------------------------------------------------------------------
    |
    | Keys come from the environment as a comma-separated list of
    | `name:key:abilities` entries (abilities are pipe-separated, `read` by
    | default), plus a single-key shorthand. With no key configured the route
    | is never registered at all — a content-management endpoint should not
    | exist unless it is deliberately switched on.
    |
    |   CONTENT_MCP_API_KEY="<generate-a-long-random-string>"
    |   CONTENT_MCP_API_KEYS="writer:<key-one>:read|write,reader:<key-two>:read"
    |
    */

    'mcp' => [
        'enabled' => env('CONTENT_MCP_ENABLED', true),
        'path' => env('CONTENT_MCP_PATH', 'mcp/content'),
        'middleware' => ['api'],
        'throttle' => env('CONTENT_MCP_THROTTLE', '60,1'),

        'keys' => array_values(array_filter(array_merge(
            env('CONTENT_MCP_API_KEY') ? [[
                'name' => 'default',
                'key' => (string) env('CONTENT_MCP_API_KEY'),
                'abilities' => ['read', 'write'],
            ]] : [],
            array_map(static function (string $entry): ?array {
                $parts = explode(':', trim($entry));

                if (count($parts) < 2 || trim($parts[1]) === '') {
                    return null;
                }

                return [
                    'name' => trim($parts[0]),
                    'key' => trim($parts[1]),
                    'abilities' => array_values(array_filter(array_map(
                        'trim',
                        explode('|', $parts[2] ?? 'read')
                    ))),
                ];
            }, array_filter(array_map('trim', explode(',', (string) env('CONTENT_MCP_API_KEYS', '')))))
        ))),

        'instructions' => 'Manage this application\'s published content: blog posts with their categories and tags, CMS pages, FAQ entries and reusable content chunks. Changes are live on a public website; leave published_at empty to file a draft for a human. Read content://guidelines before writing anything, and content://taxonomy for the category and tag slugs that already exist. Full reference: https://raw.githubusercontent.com/a2zwebltd/laravel-content-manager/main/MCP.md',
    ],

    /*
    |--------------------------------------------------------------------------
    | Editorial guidelines
    |--------------------------------------------------------------------------
    |
    | Served to agents as the content://guidelines MCP resource and used to
    | build the AI draft prompt, so remote writing lands in the house voice.
    |
    */

    'editorial' => [
        'brand' => env('CONTENT_MANAGER_BRAND', env('APP_NAME', 'Laravel')),
        'description' => '',
        'audience' => '',
        'spelling' => 'American',
        'rules' => [
            'Write for one reader, in the second person.',
            'No throat-clearing introductions — open on the substance.',
            'Never fabricate statistics, studies or quotes.',
            'Structure with markdown: ## and ### headings, short paragraphs, lists and tables where they clarify.',
            'Do not repeat the title as an H1 — the template renders it.',
        ],
        'limits' => [
            'title' => 65,
            'meta_title' => 60,
            'meta_description' => 155,
            'intro' => 220,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI drafts (optional — requires laravel/ai)
    |--------------------------------------------------------------------------
    |
    | `topic_provider` is an invokable/`all()` class-string resolved from the
    | container, so a host can back its backlog with a database instead of the
    | `topics` array. `before_call` is an invokable class-string invoked with
    | (provider, model, topic) — useful for tagging the outgoing call in the
    | host's own AI cost tracing.
    |
    */

    'ai' => [
        'enabled' => env('CONTENT_AI_ENABLED', true),
        'provider' => env('CONTENT_AI_PROVIDER', 'openai'),
        'model' => env('CONTENT_AI_MODEL'),
        'max_tokens' => 16384,
        'timeout' => 300,
        'word_count' => '1200-1800',
        'topic_provider' => null,
        'topics' => [],
        'before_call' => null,
    ],

];
