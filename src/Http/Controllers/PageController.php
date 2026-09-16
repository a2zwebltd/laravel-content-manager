<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Http\Controllers;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $page): View
    {
        $model = Models::page()::query()
            ->published()
            ->where('slug', $page)
            ->firstOrFail();

        return view(config('content-manager.views.page_show'), ['page' => $model]);
    }
}
