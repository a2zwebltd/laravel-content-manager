<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Nova;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Tag extends Resource
{
    /** @var class-string<Model> */
    public static $model = \A2ZWeb\ContentManager\Models\Tag::class;

    public static $title = 'name';

    public static $search = ['id', 'name', 'slug'];

    public static function newModel()
    {
        $model = Models::tag();

        return new $model;
    }

    /** @return array<int, mixed> */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Name')
                ->rules('required', 'max:255')
                ->sortable(),

            Slug::make('Slug')
                ->from('Name')
                ->rules('required'),

            Text::make('Type')
                ->rules('max:255')
                ->sortable(),

            Number::make('Sort Order', 'sort_order')
                ->sortable()
                ->hideFromIndex(),

            Textarea::make('Content', 'content')
                ->hideFromIndex(),

            Text::make('META Title', 'meta_title')->hideFromIndex(),
            Textarea::make('META Description', 'meta_description')->hideFromIndex(),
            Text::make('META Keywords', 'meta_keywords')->hideFromIndex(),

            MorphToMany::make('Blog Posts', 'blogPosts', BlogPost::class),
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
