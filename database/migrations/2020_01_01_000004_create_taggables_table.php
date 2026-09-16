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
        if (Schema::hasTable(Tables::taggables())) {
            return;
        }

        Schema::create(Tables::taggables(), function (Blueprint $table): void {
            $table->foreignId('tag_id')->constrained(Tables::tags())->cascadeOnDelete();
            $table->morphs('taggable');

            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::taggables());
    }
};
