<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('summary')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_translations');
    }
};

