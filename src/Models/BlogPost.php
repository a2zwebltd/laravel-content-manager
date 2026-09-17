<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Models;

use A2ZWeb\ContentManager\Concerns\FiresContentEvents;
use A2ZWeb\ContentManager\Concerns\ResolvesMorphAlias;
use A2ZWeb\ContentManager\Database\Factories\BlogPostFactory;
use A2ZWeb\ContentManager\Support\Markdown;
use A2ZWeb\ContentManager\Support\Models;
use A2ZWeb\ContentManager\Support\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BlogPost extends Model implements HasMedia
{
    use FiresContentEvents;

    /** @use HasFactory<BlogPostFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use ResolvesMorphAlias;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'title',
        'subtitle',
        'intro',
        'content',
        'slug',
        'published_at',
        'is_promoted',
        'youtube_embed',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_promoted' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BlogPost $post): void {
            $post->uuid ??= Str::uuid()->toString();
            $post->slug ??= Str::slug((string) $post->title);
        });
    }

    public function getTable(): string
    {
        return Tables::blogPosts();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Models::contentCategory(),
            Tables::blogPostContentCategory(),
            'blog_post_id',
            'content_category_id',
        );
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Models::tag(), 'taggable', Tables::taggables());
    }

    /** @param Builder<BlogPost> $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** @param Builder<BlogPost> $query */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->whereNull('published_at')
            ->orWhere('published_at', '>', now());
    }

    /** @param Builder<BlogPost> $query */
    public function scopePromoted(Builder $query): Builder
    {
        return $query->where('is_promoted', true);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    /** The post body rendered from markdown to HTML. */
    public function renderedContent(): string
    {
        return Markdown::render((string) $this->content);
    }

    /** Whole minutes at the configured reading speed, never less than one. */
    public function readingTime(): int
    {
        return Markdown::readingTime((string) $this->content);
    }

    public function registerMediaCollections(): void
    {
        $disk = (string) config('content-manager.media.disk', 'public');

        $this->addMediaCollection('main')
            ->useDisk($disk)
            ->singleFile();

        $this->addMediaCollection('small')
            ->useDisk($disk)
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('full_size')
            ->width(1280);

        $this->addMediaConversion('preview')
            ->fit(Fit::Crop, 300, 300)
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 150, 150)
            ->nonQueued();
    }

    protected static function newFactory(): Factory
    {
        return BlogPostFactory::new();
    }
}
