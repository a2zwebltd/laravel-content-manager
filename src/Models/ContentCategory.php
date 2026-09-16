<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Models;

use A2ZWeb\ContentManager\Concerns\FiresContentEvents;
use A2ZWeb\ContentManager\Database\Factories\ContentCategoryFactory;
use A2ZWeb\ContentManager\Support\Models;
use A2ZWeb\ContentManager\Support\Tables;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ContentCategory extends Model
{
    use FiresContentEvents;

    /** @use HasFactory<ContentCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'content',
    ];

    protected static function booted(): void
    {
        static::creating(function (ContentCategory $category): void {
            $category->slug ??= Str::slug((string) $category->name);
        });
    }

    public function getTable(): string
    {
        return Tables::contentCategories();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function blogPosts(): BelongsToMany
    {
        return $this->belongsToMany(
            Models::blogPost(),
            Tables::blogPostContentCategory(),
            'content_category_id',
            'blog_post_id',
        );
    }

    protected static function newFactory(): Factory
    {
        return ContentCategoryFactory::new();
    }
}
