<?php

use A2ZWeb\ContentManager\Support\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A host that owned this table before adopting the package keeps its
        // own schema and history; this migration then records itself as run
        // without touching anything.
        if (Schema::hasTable(Tables::blogPostContentCategory())) {
            return;
        }

        Schema::create(Tables::blogPostContentCategory(), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blog_post_id')->constrained(Tables::blogPosts())->cascadeOnDelete();
            $table->foreignId('content_category_id')->constrained(Tables::contentCategories())->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['blog_post_id', 'content_category_id'], 'bp_content_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::blogPostContentCategory());
    }
};
