<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Database\Factories;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class ContentCategoryFactory extends Factory
{
    /** @return class-string<Model> */
    public function modelName(): string
    {
        return Models::contentCategory();
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'content' => fake()->optional()->paragraph(),
        ];
    }
}
