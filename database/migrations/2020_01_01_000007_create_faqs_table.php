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
        if (Schema::hasTable(Tables::faqs())) {
            return;
        }

        Schema::create(Tables::faqs(), function (Blueprint $table): void {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('group')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::faqs());
    }
};
