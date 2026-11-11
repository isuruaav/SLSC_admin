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
        Schema::create('brigade_commanders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 10)->nullable();
            $table->string('name_en', 255);
            $table->string('name_si', 255)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('period_en', 255)->nullable();
            $table->string('period_si', 255)->nullable();
            $table->text('secondary_tenure_en')->nullable();
            $table->text('secondary_tenure_si')->nullable();
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
        Schema::dropIfExists('brigade_commanders');
    }
};

