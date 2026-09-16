<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Nova;

use Laravel\Nova\Resource as NovaResource;

/**
 * Base for the package's Nova resources. `group` is resolved from config
 * rather than set as a static property, because the property is shared with
 * Nova's own base class — assigning it would move every other resource in the
 * application into the same menu section.
 */
abstract class Resource extends NovaResource
{
    public static function group(): string
    {
        return (string) config('content-manager.nova.group', 'Content');
    }
}
