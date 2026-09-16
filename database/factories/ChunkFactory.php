<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Database\Factories;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class ChunkFactory extends Factory
{
    /** @return class-string<Model> */
    public function modelName(): string
    {
        return Models::chunk();
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'code' => fake()->unique()->slug(2),
            'content' => fake()->paragraph(),
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
