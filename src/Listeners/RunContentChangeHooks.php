<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Listeners;

use A2ZWeb\ContentManager\Events\ContentDeleted;
use A2ZWeb\ContentManager\Events\ContentSaved;
use Illuminate\Contracts\Container\Container;

/**
 * Invokes the class-strings listed in `content-manager.events.on_change`.
 * They are class-strings rather than closures so the host's config still
 * survives `config:cache`.
 */
class RunContentChangeHooks
{
    public function __construct(private Container $container) {}

    public function handle(ContentSaved|ContentDeleted $event): void
    {
        foreach ((array) config('content-manager.events.on_change', []) as $hook) {
            if (! is_string($hook) || ! class_exists($hook)) {
                continue;
            }

            $this->container->make($hook)($event);
        }
    }
}
