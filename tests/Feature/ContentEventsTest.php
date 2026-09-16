<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\Events\ContentDeleted;
use A2ZWeb\ContentManager\Events\ContentSaved;
use A2ZWeb\ContentManager\Listeners\RunContentChangeHooks;
use A2ZWeb\ContentManager\Models\BlogPost;
use A2ZWeb\ContentManager\Models\Faq;
use Illuminate\Support\Facades\Event;

it('announces saves and deletes with the content type', function (): void {
    Event::fake([ContentSaved::class, ContentDeleted::class]);

    $post = BlogPost::factory()->create();
    $post->update(['title' => 'Changed']);
    $post->delete();

    Event::assertDispatched(ContentSaved::class, fn (ContentSaved $e) => $e->type() === 'blog_post');
    Event::assertDispatchedTimes(ContentSaved::class, 2);
    Event::assertDispatched(ContentDeleted::class);
});

it('announces changes for every content type', function (): void {
    Event::fake([ContentSaved::class]);

    Faq::factory()->create();

    Event::assertDispatched(ContentSaved::class, fn (ContentSaved $e) => $e->type() === 'faq');
});

it('stays quiet when events are disabled', function (): void {
    config()->set('content-manager.events.enabled', false);

    Event::fake([ContentSaved::class]);

    BlogPost::factory()->create();

    Event::assertNotDispatched(ContentSaved::class);
});

it('runs invokable class-string hooks', function (): void {
    config()->set('content-manager.events.on_change', [RecordingHook::class]);

    Event::listen([ContentSaved::class], RunContentChangeHooks::class);

    BlogPost::factory()->create();

    expect(RecordingHook::$calls)->toBe(1);
});

class RecordingHook
{
    public static int $calls = 0;

    public function __invoke($event): void
    {
        self::$calls++;
    }
}
