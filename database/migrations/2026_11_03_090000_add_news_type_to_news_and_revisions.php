<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('news') && ! Schema::hasColumn('news', 'news_type')) {
            Schema::table('news', function (Blueprint $table): void {
                $table->string('news_type', 30)->default('news_feature')->index()->after('category_id');
            });
        }

        if (Schema::hasTable('news_revisions') && ! Schema::hasColumn('news_revisions', 'news_type')) {
            Schema::table('news_revisions', function (Blueprint $table): void {
                $table->string('news_type', 30)->nullable()->after('category_id');
            });
        }

        // Ensure clean categories exist
        if (Schema::hasTable('news_categories')) {
            // Update or create Category 1 as "News Feature"
            $cat1 = DB::table('news_categories')->where('id', 1)->first();
            if ($cat1) {
                DB::table('news_categories')->where('id', 1)->update([
                    'name' => 'News Feature',
                    'slug' => 'news-feature',
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('news_categories')->insert([
                    'id' => 1,
                    'name' => 'News Feature',
                    'slug' => 'news-feature',
                    'is_active' => true,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Update or create Category 2 as "Photo Story"
            $cat2 = DB::table('news_categories')->where('id', 2)->first();
            if ($cat2) {
                DB::table('news_categories')->where('id', 2)->update([
                    'name' => 'Photo Story',
                    'slug' => 'photo-story',
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('news_categories')->insert([
                    'id' => 2,
                    'name' => 'Photo Story',
                    'slug' => 'photo-story',
                    'is_active' => true,
                    'sort_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Sync existing news records
        if (Schema::hasTable('news') && Schema::hasColumn('news', 'news_type')) {
            DB::table('news')->where('category_id', 2)->update(['news_type' => 'photo_story']);
            DB::table('news')->where('category_id', '!=', 2)->orWhereNull('category_id')->update(['news_type' => 'news_feature']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('news_revisions') && Schema::hasColumn('news_revisions', 'news_type')) {
            Schema::table('news_revisions', function (Blueprint $table): void {
                $table->dropColumn('news_type');
            });
        }

        if (Schema::hasTable('news') && Schema::hasColumn('news', 'news_type')) {
            Schema::table('news', function (Blueprint $table): void {
                $table->dropColumn('news_type');
            });
        }
    }
};

