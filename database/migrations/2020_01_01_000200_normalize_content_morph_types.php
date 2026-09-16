<?php

use A2ZWeb\ContentManager\Support\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrites morph types stored as fully-qualified class names into the package's
 * morph aliases.
 *
 * Tag pivots and media rows keep the model class as a literal string. An app
 * that had its own App\Models\BlogPost before adopting this package therefore
 * holds rows pointing at a class that no longer exists — and the failure is
 * silent: tags come back empty and images disappear while every page still
 * returns 200. This is a no-op on a fresh install.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rewrite(config('content-manager.legacy_morph_types', []));
    }

    public function down(): void
    {
        $this->rewrite(array_flip(config('content-manager.legacy_morph_types', [])));
    }

    /**
     * @param  array<string, string>  $map
     */
    private function rewrite(array $map): void
    {
        foreach ($map as $from => $to) {
            if (Schema::hasTable(Tables::taggables())) {
                DB::table(Tables::taggables())
                    ->where('taggable_type', $from)
                    ->update(['taggable_type' => $to]);
            }

            if (Schema::hasTable('media')) {
                DB::table('media')
                    ->where('model_type', $from)
                    ->update(['model_type' => $to]);
            }
        }
    }
};
