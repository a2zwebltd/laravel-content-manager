<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Models;

use A2ZWeb\ContentManager\Concerns\FiresContentEvents;
use A2ZWeb\ContentManager\Database\Factories\FaqFactory;
use A2ZWeb\ContentManager\Support\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use FiresContentEvents;

    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'group',
        'published_at',
        'sort_order',
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
        return Tables::faqs();
    }

    /** @param Builder<Faq> $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Group entries so a page can ask for its own set by name instead of
     * slicing on sort_order ranges.
     *
     * @param  Builder<Faq>  $query
     */
    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    protected static function newFactory(): Factory
    {
        return FaqFactory::new();
    }
}
