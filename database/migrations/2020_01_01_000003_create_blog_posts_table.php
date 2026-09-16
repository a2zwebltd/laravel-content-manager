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
        if (Schema::hasTable(Tables::blogPosts())) {
            return;
        }

        Schema::create(Tables::blogPosts(), function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('intro')->nullable();
            $table->longText('content')->nullable();
            $table->string('slug')->unique();
            $table->timestamp('published_at')->nullable()->index();
            $table->boolean('is_promoted')->default(false);
            $table->string('youtube_embed')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::blogPosts());
    }
};
