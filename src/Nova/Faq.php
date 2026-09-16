<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Nova;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;

class Faq extends Resource
{
    /** @var class-string<Model> */
    public static $model = \A2ZWeb\ContentManager\Models\Faq::class;

    public static $title = 'question';

    public static $search = ['id', 'question', 'answer'];

    public static function label(): string
    {
        return 'FAQs';
    }

    public static function newModel()
    {
        $model = Models::faq();

        return new $model;
    }

    /** @return array<int, mixed> */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Question')
                ->rules('required', 'max:255')
                ->sortable(),

            Trix::make('Answer')
                ->rules('required')
                ->hideFromIndex(),

            Text::make('Group')
                ->sortable()
                ->help('Lets a page render just its own set of entries.'),

            Number::make('Sort Order', 'sort_order')
                ->sortable(),

            DateTime::make('Published At', 'published_at')
                ->sortable(),
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
