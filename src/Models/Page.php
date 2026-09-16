<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Models;

use A2ZWeb\ContentManager\Concerns\FiresContentEvents;
use A2ZWeb\ContentManager\Database\Factories\PageFactory;
use A2ZWeb\ContentManager\Support\Markdown;
use A2ZWeb\ContentManager\Support\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use FiresContentEvents;

    /** @use HasFactory<PageFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'meta_description',
        'content',
        'sort_order',
        'published_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return Tables::pages();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Route binding is scoped to published pages, so a draft 404s instead of
     * leaking. Anything that needs the draft queries the model directly.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->published()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }

    /** @param Builder<Page> $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function renderedContent(): string
    {
        return Markdown::render((string) $this->content);
    }

    protected static function newFactory(): Factory
    {
        return PageFactory::new();
    }
}
