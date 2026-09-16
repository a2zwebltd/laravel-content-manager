<?php

use A2ZWeb\ContentManager\Http\Controllers\BlogController;
use A2ZWeb\ContentManager\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
 * Registered by the service provider inside a group that supplies the prefix,
 * name prefix and middleware from config. Ordering matters: /blog/tags must be
 * declared before /blog/{blogPost}, and the page catch-all comes last.
 */

$blog = trim((string) config('content-manager.routes.blog_prefix', 'blog'), '/');
$names = (array) config('content-manager.routes.names', []);

Route::get($blog, [BlogController::class, 'index'])->name($names['index'] ?? 'blog.index');
Route::get($blog.'/tags', [BlogController::class, 'tags'])->name($names['tags'] ?? 'blog.tags');
Route::get($blog.'/tag/{tag}', [BlogController::class, 'tag'])->name($names['tag'] ?? 'blog.tag');
Route::get($blog.'/category/{contentCategory}', [BlogController::class, 'category'])->name($names['category'] ?? 'blog.category');
Route::get($blog.'/{blogPost}', [BlogController::class, 'show'])->name($names['show'] ?? 'blog.show');

if (config('content-manager.routes.pages.enabled', true)) {
    $slugs = (array) config('content-manager.routes.pages.slugs', []);

    $route = Route::get('/{page}', [PageController::class, 'show'])->name($names['page'] ?? 'pages.show');

    // A whitelist keeps a CMS page from shadowing a future top-level route.
    if ($slugs !== []) {
        $route->where('page', implode('|', array_map('preg_quote', $slugs)));
    }
}
