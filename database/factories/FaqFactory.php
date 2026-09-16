<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Database\Factories;

use A2ZWeb\ContentManager\Support\Models;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class FaqFactory extends Factory
{
    /** @return class-string<Model> */
    public function modelName(): string
    {
        return Models::faq();
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['published_at' => now()->subDay()]);
    }
}
