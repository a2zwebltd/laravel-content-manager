<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Database\Factories;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class BlogPostFactory extends Factory
{
    /** @return class-string<Model> */
    public function modelName(): string
    {
        return Models::blogPost();
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'subtitle' => fake()->optional()->sentence(),
            'intro' => fake()->optional()->paragraph(),
            'content' => fake()->paragraphs(3, true),
            'slug' => fake()->unique()->slug(),
            'is_promoted' => false,
            'meta_title' => fake()->optional()->sentence(),
            'meta_description' => fake()->optional()->text(160),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['published_at' => fake()->dateTimeBetween('-1 year')]);
    }

    public function promoted(): static
    {
        return $this->state(fn () => [
            'is_promoted' => true,
            'published_at' => fake()->dateTimeBetween('-1 year'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['published_at' => now()->addWeek()]);
    }
}
