<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Console\Commands;

use A2ZWeb\ContentManager\Ai\BlogDraftWriter;
use A2ZWeb\ContentManager\Ai\ConfigTopicProvider;
use A2ZWeb\ContentManager\Ai\TopicProvider;
use A2ZWeb\ContentManager\Concerns\DrainsAgentStream;
use A2ZWeb\ContentManager\Events\ContentDraftGenerated;
use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Console\Command;
use Throwable;

/**
 * Drafts the next posts from the configured backlog as UNPUBLISHED rows for a
 * human to review and publish. The command never publishes anything itself.
 *
 * Idempotent: each topic carries a fixed slug, and an existing row — even a
 * soft-deleted or still-draft one — is skipped, so re-runs simply continue
 * down the backlog.
 */
class GenerateContentDrafts extends Command
{
    use DrainsAgentStream;

    protected $signature = 'content:generate-drafts
        {--count=2 : How many drafts to generate this run}
        {--slug= : Draft one specific topic from the backlog by slug}';

    protected $description = 'Draft the next posts from the editorial backlog as unpublished posts for review.';

    public function handle(): int
    {
        $topics = collect($this->topics());

        if ($topics->isEmpty()) {
            $this->warn('The backlog is empty. Add topics to config("content-manager.ai.topics") or set a topic_provider.');

            return self::SUCCESS;
        }

        if ($slug = $this->option('slug')) {
            $topics = $topics->where('slug', $slug);

            if ($topics->isEmpty()) {
                $this->error("No topic with slug '{$slug}' in the backlog.");

                return self::FAILURE;
            }
        }

        $model = Models::blogPost();

        $pending = $topics
            ->reject(fn (array $topic) => $model::withTrashed()->where('slug', $topic['slug'])->exists())
            ->take(max(1, (int) $this->option('count')));

        if ($pending->isEmpty()) {
            $this->info('Backlog exhausted — every topic already has a post.');

            return self::SUCCESS;
        }

        $failures = 0;

        foreach ($pending as $topic) {
            $this->line("Drafting: {$topic['title_idea']} …");

            try {
                $post = $this->draft($topic);

                $this->info("  ✓ Draft #{$post->id} \"{$post->title}\" — review and publish it in the admin.");
            } catch (Throwable $e) {
                $failures++;
                $this->error("  ✗ Failed: {$e->getMessage()}");
            }
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<int, array<string, mixed>> */
    private function topics(): array
    {
        $provider = config('content-manager.ai.topic_provider');

        if (is_string($provider) && class_exists($provider)) {
            /** @var TopicProvider $instance */
            $instance = app($provider);

            return $instance->all();
        }

        return app(ConfigTopicProvider::class)->all();
    }

    /** @param array<string, mixed> $topic */
    private function draft(array $topic): object
    {
        $provider = (string) config('content-manager.ai.provider', 'openai');
        $model = config('content-manager.ai.model');

        // Lets the host tag the outgoing call for its own cost tracing.
        $hook = config('content-manager.ai.before_call');

        if (is_string($hook) && class_exists($hook)) {
            app($hook)($provider, $model, $topic);
        }

        $stream = BlogDraftWriter::make(
            titleIdea: (string) $topic['title_idea'],
            keyword: (string) ($topic['keyword'] ?? ''),
            angle: (string) ($topic['angle'] ?? ''),
        )->stream('Write the post now.', provider: $provider, model: $model);

        ['text' => $text, 'promptTokens' => $promptTokens, 'completionTokens' => $completionTokens] = $this->drainStream($stream);

        $draft = $this->parseDraftJson($text);

        $post = Models::blogPost()::query()->create([
            'slug' => $topic['slug'],
            'title' => $draft['title'] ?? $topic['title_idea'],
            'subtitle' => $draft['subtitle'] ?? null,
            'intro' => $draft['intro'] ?? null,
            'content' => $draft['content'] ?? '',
            'meta_title' => $draft['meta_title'] ?? null,
            'meta_description' => $draft['meta_description'] ?? null,
            'meta_keywords' => $draft['meta_keywords'] ?? ($topic['keyword'] ?? null),
            'published_at' => null, // a human publishes it
        ]);

        event(new ContentDraftGenerated(
            post: $post,
            topic: $topic,
            provider: $provider,
            model: is_string($model) ? $model : null,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
        ));

        return $post;
    }
}
