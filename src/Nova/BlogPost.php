<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Nova;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class BlogPost extends Resource
{
    /** @var class-string<Model> */
    public static $model = \A2ZWeb\ContentManager\Models\BlogPost::class;

    public static $title = 'title';

    public static $search = ['id', 'title', 'slug', 'intro', 'content'];

    public static function newModel()
    {
        $model = Models::blogPost();

        return new $model;
    }

    /** @return array<int, mixed> */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            DateTime::make('Published At', 'published_at')
                ->default(now())
                ->sortable(),

            Boolean::make('Promoted', 'is_promoted'),

            Text::make('Title')
                ->rules('required', 'max:255')
                ->sortable(),

            Slug::make('Slug')
                ->from('Title')
                ->rules('required')
                ->hideFromIndex(),

            Text::make('Subtitle')
                ->rules('max:255')
                ->hideFromIndex(),

            Textarea::make('Intro', 'intro')
                ->hideFromIndex(),

            Markdown::make('Content', 'content')
                ->hideFromIndex(),

            Text::make('YouTube Embed', 'youtube_embed')
                ->hideFromIndex(),

            // Stored through the media library so the admin upload and whatever
            // the front end renders are the same file. A plain disk-backed
            // Image field would write somewhere nothing reads.
            Image::make('Featured Image', 'featured_image')
                ->store(function (NovaRequest $request, $model) {
                    $model->addMediaFromRequest('featured_image')
                        ->toMediaCollection((string) config('content-manager.media.collection', 'main'));

                    return true;
                })
                ->preview(fn ($value, $disk, $model) => $model?->getFirstMediaUrl('main', 'preview') ?: null)
                ->thumbnail(fn ($value, $disk, $model) => $model?->getFirstMediaUrl('main', 'thumb') ?: null)
                ->delete(function (NovaRequest $request, $model) {
                    $model->clearMediaCollection((string) config('content-manager.media.collection', 'main'));

                    return true;
                })
                ->hideFromIndex(),

            BelongsToMany::make('Categories', 'categories', ContentCategory::class),

            MorphToMany::make('Tags', 'tags', Tag::class),

            Text::make('META Title', 'meta_title')
                ->rules('max:255')
                ->hideFromIndex(),

            Textarea::make('META Description', 'meta_description')
                ->hideFromIndex(),

            Text::make('META Keywords', 'meta_keywords')
                ->hideFromIndex(),

            DateTime::make('Created At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->hideFromIndex()
                ->sortable(),

            DateTime::make('Updated At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->hideFromIndex(),

            Text::make('UUID')
                ->onlyOnDetail()
                ->hideWhenCreating()
                ->readonly()
                ->copyable(),
        ];
    }

    /** @return array<int, mixed> */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /** @return array<int, mixed> */
    public function filters(NovaRequest $request): array
    {
        return [];
    }

    /** @return array<int, mixed> */
    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    /** @return array<int, mixed> */
    public function actions(NovaRequest $request): array
    {
        return [];
    }
}
