<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Support;

use Illuminate\Support\Facades\Route;

/**
 * Link building that survives the host owning the routes: every name comes
 * from config, and a name that isn't registered yields null instead of
 * throwing, so a host that runs the blog without, say, a tag page still gets
 * a working sitemap.
 */
class Routes
{
    public static function name(string $key): ?string
    {
        $name = config('content-manager.routes.names.'.$key);

        return is_string($name) && $name !== '' ? $name : null;
    }

    /** @param array<string, mixed>|string|null $parameters */
    public static function url(string $key, array|string|null $parameters = null): ?string
    {
        $name = self::name($key);

        if ($name === null || ! Route::has($name)) {
            return null;
        }

        return route($name, $parameters ?? []);
    }

    public static function has(string $key): bool
    {
        $name = self::name($key);

        return $name !== null && Route::has($name);
    }
}
