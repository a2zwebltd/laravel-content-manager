<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\Models\Faq;
use A2ZWeb\ContentManager\Support\ContentIndex;

it('only counts published entries as published', function (): void {
    Faq::factory()->published()->create();
    Faq::factory()->create(['published_at' => null]);
    Faq::factory()->create(['published_at' => now()->addWeek()]);

    expect(Faq::published()->count())->toBe(1);
});

it('limits a listing to one group', function (): void {
    Faq::factory()->published()->create(['group' => 'pricing', 'question' => 'What does it cost?']);
    Faq::factory()->published()->create(['group' => 'general', 'question' => 'What is this?']);

    $faqs = ContentIndex::faqs('pricing');

    expect($faqs)->toHaveCount(1)
        ->and($faqs[0]['question'])->toBe('What does it cost?');
});
