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
        if (Schema::hasTable(Tables::pages())) {
            return;
        }

        Schema::create(Tables::pages(), function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('meta_description', 255)->nullable();
            $table->longText('content');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::pages());
    }
};
