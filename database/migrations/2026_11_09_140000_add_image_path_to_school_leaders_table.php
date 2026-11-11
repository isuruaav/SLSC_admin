<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_leaders') && ! Schema::hasColumn('school_leaders', 'image_path')) {
            Schema::table('school_leaders', function (Blueprint $table): void {
                $table->string('image_path', 255)->nullable()->after('image_media_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('school_leaders') && Schema::hasColumn('school_leaders', 'image_path')) {
            Schema::table('school_leaders', function (Blueprint $table): void {
                $table->dropColumn('image_path');
            });
        }
    }
};

