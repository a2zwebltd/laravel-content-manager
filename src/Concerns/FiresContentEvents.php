<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Concerns;

use A2ZWeb\ContentManager\Events\ContentDeleted;
use A2ZWeb\ContentManager\Events\ContentSaved;
use Illuminate\Database\Eloquent\Model;

/**
 * Announces content changes without knowing what the host does about them.
 * The package ships a listener that clears the HTTP response cache; hosts hang
 * sitemap rebuilds, CDN purges or search pings off the same two events.
 */
trait FiresContentEvents
{
    public static function bootFiresContentEvents(): void
    {
        static::created(static fn (Model $model) => self::announce(new ContentSaved($model)));
        static::updated(static fn (Model $model) => self::announce(new ContentSaved($model)));
        static::deleted(static fn (Model $model) => self::announce(new ContentDeleted($model)));

        if (method_exists(static::class, 'restored')) {
            static::restored(static fn (Model $model) => self::announce(new ContentSaved($model)));
        }
    }

    /**
     * Announce a change Eloquent cannot see — a pivot sync, a new image — the
     * same way a save would. touch() is not enough on its own: within the
     * same second updated_at does not change, so nothing is dirty and no
     * updated event fires.
     */
    public function announceContentChange(): void
    {
        if ($this->usesTimestamps()) {
            $this->touchQuietly();
        }

        self::announce(new ContentSaved($this));
    }

    private static function announce(ContentSaved|ContentDeleted $event): void
    {
        if (! config('content-manager.events.enabled', true)) {
            return;
        }

        event($event);
    }
}
