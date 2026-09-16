<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Listeners;

use A2ZWeb\ContentManager\Events\ContentDeleted;
use A2ZWeb\ContentManager\Events\ContentSaved;
use Illuminate\Support\Facades\App;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * Clears the public HTTP cache when content changes. Registered only when
 * spatie/laravel-responsecache is installed and the config allows it.
 */
class FlushResponseCache
{
    public function handle(ContentSaved|ContentDeleted $event): void
    {
        // Tests churn content through factories; flushing (and whatever the
        // host hangs off it, such as a sitemap rebuild) would be pure noise.
        if (App::environment('testing')) {
            return;
        }

        ResponseCache::clear();
    }
}
