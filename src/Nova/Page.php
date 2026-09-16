<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Nova;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Page extends Resource
{
    /** @var class-string<Model> */
    public static $model = \A2ZWeb\ContentManager\Models\Page::class;

    public static $title = 'title';

    public static $search = ['id', 'title', 'slug', 'content'];

    public static function newModel()
    {
        $model = Models::page();

        return new $model;
    }

    /** @return array<int, mixed> */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Title')
                ->rules('required', 'max:255')
                ->sortable(),

            Slug::make('Slug')
                ->from('Title')
                ->rules('required')
                ->help('The URL segment. Changing it breaks existing links.'),

            Textarea::make('META Description', 'meta_description')
                ->rules('max:255')
                ->hideFromIndex(),

            Markdown::make('Content', 'content')
                ->rules('required')
                ->hideFromIndex(),

            Number::make('Sort Order', 'sort_order')
                ->sortable(),

            DateTime::make('Published At', 'published_at')
                ->sortable()
                ->help('Empty or future-dated pages return a 404.'),
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
