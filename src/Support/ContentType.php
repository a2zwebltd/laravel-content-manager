<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Support;

use Illuminate\Database\Eloquent\Model;

class ContentType
{
    /** blog_post, content_category, tag, page, faq or chunk. */
    public static function of(Model $model): string
    {
        foreach (Models::all() as $type => $class) {
            if ($model instanceof $class) {
                return $type;
            }
        }

        return class_basename($model);
    }
}
