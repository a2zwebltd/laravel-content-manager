<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Nova;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Chunk extends Resource
{
    /** @var class-string<Model> */
    public static $model = \A2ZWeb\ContentManager\Models\Chunk::class;

    public static $title = 'name';

    public static $search = ['id', 'name', 'code', 'content'];

    public static function newModel()
    {
        $model = Models::chunk();

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

            Text::make('Code')
                ->rules('required', 'max:255')
                ->sortable()
                ->help('Templates render this chunk by code — renaming it makes them fall back to nothing.'),

            Textarea::make('Content', 'content')
                ->alwaysShow(),

            Boolean::make('Published', 'is_published'),
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
