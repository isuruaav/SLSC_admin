<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('locale', 10)->default('en')->index();
            $table->string('block_type', 50)->index();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->json('structured_data')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();

            $table->index(['organization_id', 'locale', 'sort_order'], 'org_blocks_org_loc_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_content_blocks');
    }
};

