<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('publications_title_en')->nullable()->default('Signaller Magazine');
            $table->string('publications_title_si')->nullable()->default('සිග්නලර් සඟරාව');
            $table->string('publications_kicker_en')->nullable()->default('Explore our library');
            $table->string('publications_kicker_si')->nullable()->default('අපගේ සඟරා');
            $table->text('publications_subtitle_en')->nullable();
            $table->text('publications_subtitle_si')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'publications_title_en',
                'publications_title_si',
                'publications_kicker_en',
                'publications_kicker_si',
                'publications_subtitle_en',
                'publications_subtitle_si',
            ]);
        });
    }
};

