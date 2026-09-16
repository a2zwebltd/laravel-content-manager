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
        if (Schema::hasTable(Tables::chunks())) {
            return;
        }

        Schema::create(Tables::chunks(), function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->longText('content')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::chunks());
    }
};
