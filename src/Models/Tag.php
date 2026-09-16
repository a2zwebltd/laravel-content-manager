<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Models;

use A2ZWeb\ContentManager\Concerns\FiresContentEvents;
use A2ZWeb\ContentManager\Database\Factories\TagFactory;
use A2ZWeb\ContentManager\Support\Models;
use A2ZWeb\ContentManager\Support\Tables;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use FiresContentEvents;

    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'sort_order',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tag $tag): void {
            $tag->slug ??= Str::slug((string) $tag->name);
        });
    }

    public function getTable(): string
    {
        return Tables::tags();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function blogPosts(): MorphToMany
    {
        return $this->morphedByMany(Models::blogPost(), 'taggable', Tables::taggables());
    }

    protected static function newFactory(): Factory
    {
        return TagFactory::new();
    }
}
