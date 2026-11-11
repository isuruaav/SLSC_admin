<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('first_appointment_holders', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_en', 255);
            $table->string('appointment_si', 255)->nullable();
            $table->string('name_en', 255);
            $table->string('name_si', 255)->nullable();
            $table->string('period_en', 100)->nullable();
            $table->string('period_si', 100)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->foreignId('image_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('first_appointment_holders');
    }
};

