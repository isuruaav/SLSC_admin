<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_leaders')) {
            Schema::table('school_leaders', function (Blueprint $table): void {
                if (! Schema::hasColumn('school_leaders', 'secondary_tenure_en')) {
                    $table->text('secondary_tenure_en')->nullable()->after('end_date');
                }
                if (! Schema::hasColumn('school_leaders', 'secondary_tenure_si')) {
                    $table->text('secondary_tenure_si')->nullable()->after('secondary_tenure_en');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('school_leaders')) {
            Schema::table('school_leaders', function (Blueprint $table): void {
                $columns = [];
                if (Schema::hasColumn('school_leaders', 'secondary_tenure_en')) {
                    $columns[] = 'secondary_tenure_en';
                }
                if (Schema::hasColumn('school_leaders', 'secondary_tenure_si')) {
                    $columns[] = 'secondary_tenure_si';
                }
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};

