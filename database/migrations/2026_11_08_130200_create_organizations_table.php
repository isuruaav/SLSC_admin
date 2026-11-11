<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('organization_categories')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('slug', 191)->unique();
            $table->string('short_name', 100)->nullable();
            $table->foreignId('featured_image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('featured_image_url', 500)->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('email')->nullable();
            $table->string('telephone', 100)->nullable();
            $table->string('fax', 100)->nullable();
            $table->string('location')->nullable();
            $table->string('website', 500)->nullable();
            $table->string('source_url', 500)->nullable()->index();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};

