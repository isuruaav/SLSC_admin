<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            // Trashed pages keep their original URL without reserving it.
            $table->string('active_slug')
                ->nullable()
                ->virtualAs('case when deleted_at is null then slug else null end');

            $table->unique(['locale', 'active_slug'], 'pages_locale_active_slug_unique');
            $table->dropUnique('pages_locale_slug_unique');
            $table->index(['locale', 'slug'], 'pages_locale_slug_index');
        });
    }

    public function down(): void
    {
        if (DB::table('pages')->select('locale', 'slug')
            ->groupBy('locale', 'slug')->havingRaw('count(*) > 1')->exists()) {
            throw new RuntimeException('Resolve reused page slugs before rolling back this migration.');
        }

        Schema::table('pages', function (Blueprint $table): void {
            $table->unique(['locale', 'slug'], 'pages_locale_slug_unique');
            $table->dropUnique('pages_locale_active_slug_unique');
            $table->dropIndex('pages_locale_slug_index');
            $table->dropColumn('active_slug');
        });
    }
};
