<?php

declare(strict_types=1);

use A2ZWeb\ContentManager\Models\BlogPost;
use A2ZWeb\ContentManager\Models\Tag;
use A2ZWeb\ContentManager\Support\Tables;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

it('stores tag pivots under the morph alias, not a class name', function (): void {
    $post = BlogPost::factory()->published()->create();
    $post->tags()->attach(Tag::factory()->create());

    expect(DB::table(Tables::taggables())->value('taggable_type'))->toBe('blog_post');
});

it('resolves the alias back to the configured model', function (): void {
    expect(Relation::getMorphedModel('blog_post'))->toBe(BlogPost::class);
});

it('reads tags from rows written under a legacy class name', function (): void {
    // What an app's table looks like before it adopts the package: the morph
    // type is the old application class, which no longer exists.
    $post = BlogPost::factory()->published()->create();
    $tag = Tag::factory()->create(['name' => 'Legacy tag']);

    DB::table(Tables::taggables())->insert([
        'tag_id' => $tag->id,
        'taggable_id' => $post->id,
        'taggable_type' => 'App\Models\BlogPost',
    ]);

    expect($post->tags()->count())->toBe(0);

    // The normalize migration is what closes that gap.
    foreach (config('content-manager.legacy_morph_types') as $legacy => $alias) {
        DB::table(Tables::taggables())->where('taggable_type', $legacy)->update(['taggable_type' => $alias]);
    }

    expect($post->load('tags')->tags->pluck('name')->all())->toBe(['Legacy tag']);
});
