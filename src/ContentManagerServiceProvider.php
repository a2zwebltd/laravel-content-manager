<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager;

use A2ZWeb\ContentManager\Ai\ConfigTopicProvider;
use A2ZWeb\ContentManager\Console\Commands\GenerateContentDrafts;
use A2ZWeb\ContentManager\Console\Commands\McpStatus;
use A2ZWeb\ContentManager\Events\ContentDeleted;
use A2ZWeb\ContentManager\Events\ContentSaved;
use A2ZWeb\ContentManager\Http\Middleware\EnsureContentApiKey;
use A2ZWeb\ContentManager\Listeners\FlushResponseCache;
use A2ZWeb\ContentManager\Listeners\RunContentChangeHooks;
use A2ZWeb\ContentManager\Mcp\ContentServer;
use A2ZWeb\ContentManager\Nova\BlogPost;
use A2ZWeb\ContentManager\Nova\Chunk;
use A2ZWeb\ContentManager\Nova\ContentCategory;
use A2ZWeb\ContentManager\Nova\Faq;
use A2ZWeb\ContentManager\Nova\Page;
use A2ZWeb\ContentManager\Nova\Tag;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server as McpServer;
use Laravel\Nova\Nova;
use Spatie\ResponseCache\Facades\ResponseCache;

class ContentManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/content-manager.php', 'content-manager');

        $this->app->singleton(ConfigTopicProvider::class);
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerMigrations();
        $this->registerViews();
        $this->registerMorphMap();
        $this->registerListeners();
        $this->registerRoutes();
        $this->registerMcp();
        $this->registerNova();
        $this->registerCommands();
    }

    private function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/content-manager.php' => config_path('content-manager.php'),
        ], 'content-manager-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/content-manager'),
        ], 'content-manager-views');
    }

    private function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    private function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'content-manager');
    }

    /**
     * Tag pivots and media rows store the morph type as a string, so aliasing
     * it is what lets these models move between namespaces — or be subclassed
     * by a host — without orphaning every tag and image.
     */
    private function registerMorphMap(): void
    {
        $map = array_filter((array) config('content-manager.morph_map', []));

        if ($map !== []) {
            Relation::morphMap($map);
        }
    }

    private function registerListeners(): void
    {
        if (! config('content-manager.events.enabled', true)) {
            return;
        }

        if (config('content-manager.events.flush_response_cache', true) && class_exists(ResponseCache::class)) {
            Event::listen([ContentSaved::class, ContentDeleted::class], FlushResponseCache::class);
        }

        if ((array) config('content-manager.events.on_change', []) !== []) {
            Event::listen([ContentSaved::class, ContentDeleted::class], RunContentChangeHooks::class);
        }
    }

    private function registerRoutes(): void
    {
        if (! config('content-manager.routes.enabled', true)) {
            return;
        }

        Route::group([
            'prefix' => config('content-manager.routes.prefix', ''),
            'as' => config('content-manager.routes.name_prefix', ''),
            'middleware' => config('content-manager.routes.middleware', ['web']),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * The MCP endpoint only exists once a key is configured. A content-management
     * endpoint that answers at all is an attack surface, and "401 for everyone"
     * is one config mistake away from "open to everyone".
     */
    /**
     * Whether the MCP endpoint should exist at all. Public so the status
     * command — and tests — can ask the same question the provider asks,
     * rather than inferring it from the route table.
     */
    public static function mcpIsAvailable(): bool
    {
        return (bool) config('content-manager.mcp.enabled', true)
            && class_exists(McpServer::class)
            && (array) config('content-manager.mcp.keys', []) !== [];
    }

    private function registerMcp(): void
    {
        if (! self::mcpIsAvailable()) {
            return;
        }

        $middleware = array_values(array_filter([
            ...(array) config('content-manager.mcp.middleware', ['api']),
            ($throttle = config('content-manager.mcp.throttle')) ? 'throttle:'.$throttle : null,
            EnsureContentApiKey::class,
        ]));

        Mcp::web((string) config('content-manager.mcp.path', 'mcp/content'), ContentServer::class)
            ->middleware($middleware);
    }

    private function registerNova(): void
    {
        if (! config('content-manager.nova.register_resources', true) || ! class_exists(Nova::class)) {
            return;
        }

        // Fully qualified: `use Laravel\Nova\Nova` above shadows the relative
        // `Nova\…` prefix, which would resolve to Laravel\Nova\Nova\BlogPost.
        Nova::resources([
            BlogPost::class,
            ContentCategory::class,
            Tag::class,
            Page::class,
            Faq::class,
            Chunk::class,
        ]);
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $commands = [McpStatus::class];

        // The draft pipeline is only wired up when the AI SDK is actually
        // installed, so the package stays installable without it.
        if (config('content-manager.ai.enabled', true) && interface_exists(Agent::class)) {
            $commands[] = GenerateContentDrafts::class;
        }

        $this->commands($commands);
    }
}
