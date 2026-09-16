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
        if (Schema::hasTable(Tables::tags())) {
            return;
        }

        Schema::create(Tables::tags(), function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->nullable()->index();
            $table->integer('sort_order')->default(0);
            $table->text('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::tags());
    }
};
