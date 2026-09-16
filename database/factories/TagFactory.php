<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Database\Factories;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class TagFactory extends Factory
{
    /** @return class-string<Model> */
    public function modelName(): string
    {
        return Models::tag();
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(1),
            'type' => 'blog',
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
