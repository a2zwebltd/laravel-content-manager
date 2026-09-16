<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Concerns;

/**
 * Keeps the morph alias stable when a host swaps in its own subclass.
 *
 * Laravel's default only matches the exact class registered in the morph map,
 * so an app that points `models.blog_post` at a subclass would find that the
 * parent class silently stops matching its own tag pivots — every tag lookup
 * returning empty with no error anywhere. Resolving up and down the hierarchy
 * makes both classes answer to the same alias.
 */
trait ResolvesMorphAlias
{
    public function getMorphClass(): string
    {
        foreach ((array) config('content-manager.morph_map', []) as $alias => $class) {
            if (! is_string($class)) {
                continue;
            }

            if ($this instanceof $class || is_subclass_of($class, static::class)) {
                return (string) $alias;
            }
        }

        return parent::getMorphClass();
    }
}
