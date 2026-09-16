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
        if (Schema::hasTable(Tables::contentCategories())) {
            return;
        }

        Schema::create(Tables::contentCategories(), function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('content')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::contentCategories());
    }
};
