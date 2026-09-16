<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Nova;

use A2ZWeb\ContentManager\Support\Models;
use A2ZWeb\ContentManager\Support\Tables;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class ContentCategory extends Resource
{
    /** @var class-string<Model> */
    public static $model = \A2ZWeb\ContentManager\Models\ContentCategory::class;

    public static $title = 'name';

    public static $search = ['id', 'name', 'slug'];

    public static function newModel()
    {
        $model = Models::contentCategory();

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
                ->rules('required')
                ->creationRules('unique:'.Tables::contentCategories().',slug')
                ->updateRules('unique:'.Tables::contentCategories().',slug,{{resourceId}}'),

            Textarea::make('Content', 'content')
                ->hideFromIndex(),

            BelongsToMany::make('Blog Posts', 'blogPosts', BlogPost::class),
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
