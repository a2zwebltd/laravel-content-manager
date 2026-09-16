<?php

use A2ZWeb\ContentManager\Support\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds any column the package expects but a pre-existing host table lacks.
 * On a fresh install every check is already satisfied and nothing runs; on a
 * host that owned these tables before adopting the package (so the create
 * migrations above no-opped) this is what closes the gap.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable(Tables::faqs()) && ! Schema::hasColumn(Tables::faqs(), 'group')) {
            Schema::table(Tables::faqs(), function (Blueprint $table): void {
                $table->string('group')->nullable()->index()->after('answer');
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty. On a fresh install the column belongs to the
        // create migration above, and dropping it here would fight that
        // migration's own rollback; on a host that already owned the table,
        // dropping a column we may not have added would lose data.
    }
};
