<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Database\Factories;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class PageFactory extends Factory
{
    /** @return class-string<Model> */
    public function modelName(): string
    {
        return Models::page();
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'title' => fake()->sentence(),
            'meta_description' => fake()->optional()->text(160),
            'content' => fake()->paragraphs(3, true),
            'sort_order' => 0,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['published_at' => now()->subDay()]);
    }
}
