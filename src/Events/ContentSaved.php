<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Events;

use A2ZWeb\ContentManager\Support\ContentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class ContentSaved
{
    use Dispatchable;

    public function __construct(public Model $model) {}

    /** The morph-style slug of what changed: blog_post, page, faq, … */
    public function type(): string
    {
        return ContentType::of($this->model);
    }
}
