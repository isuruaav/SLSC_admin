<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('special_programs', function (Blueprint $table) {
            $table->id();
            $table->string('title_en', 255);
            $table->string('title_si', 255)->nullable();
            $table->string('url', 500);
            $table->string('category', 50)->default('heritage');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('open_in_new_tab')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('category');
        });

        // Seed the 15 default special programs
        $now = now();
        $defaults = [
            [
                'title_en' => 'Code of Conduct',
                'title_si' => 'ආචාර ධර්ම පද්ධතිය',
                'url' => 'https://alt.army.lk/public/code_of_conduct.pdf',
                'category' => 'heritage',
                'sort_order' => 1,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'Signal Rugby Theme Song',
                'title_si' => 'සංඥා රග්බි තේමා ගීතය',
                'url' => 'https://alt.army.lk/signaller/rugby_song',
                'category' => 'heritage',
                'sort_order' => 2,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'Signal Flag',
                'title_si' => 'සංඥා ධජය',
                'url' => 'https://alt.army.lk/signaller/flag',
                'category' => 'heritage',
                'sort_order' => 3,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'Signal Song',
                'title_si' => 'සංඥා බලකා ගීතය',
                'url' => 'https://alt.army.lk/signaller/song',
                'category' => 'heritage',
                'sort_order' => 4,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'Signal March',
                'title_si' => 'සංඥා බලකා පෙළපාළි ගීතය',
                'url' => 'https://alt.army.lk/signaller/signal-march',
                'category' => 'heritage',
                'sort_order' => 5,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'Supreme Sacrifices',
                'title_si' => 'උත්තරීතර පරිත්‍යාගයන්',
                'url' => 'https://alt.army.lk/signaller/supreme-sacrifices',
                'category' => 'heritage',
                'sort_order' => 6,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => '75th Anniversary Sri Lanka Signal Corps',
                'title_si' => 'ශ්‍රී ලංකා සංඥා බලකායේ 75 වන සංවත්සරය',
                'url' => 'https://alt.army.lk/signaller/content/commander-army-visited-corps-signals-respect-signallers-their-75th-anniversary',
                'category' => 'heritage',
                'sort_order' => 7,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'EX - Servicemen',
                'title_si' => 'විශ්‍රාමික හමුදා සාමාජිකයන්',
                'url' => 'https://alt.army.lk/signaller/ex-servicemen',
                'category' => 'community',
                'sort_order' => 8,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'Junior Club',
                'title_si' => 'කනිෂ්ඨ සමාජය',
                'url' => 'https://alt.army.lk/signaller/junior-club',
                'category' => 'community',
                'sort_order' => 9,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'Signal Seva Vanitha Branch',
                'title_si' => 'සංඥා සේවා වනිතා ශාඛාව',
                'url' => 'https://alt.army.lk/slsc_svb/',
                'category' => 'community',
                'sort_order' => 10,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'Professional Achievements',
                'title_si' => 'වෘත්තීය ජයග්‍රහණ',
                'url' => 'https://alt.army.lk/signaller/professional-achievements',
                'category' => 'resources',
                'sort_order' => 11,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'Holiday Bungalows',
                'title_si' => 'නිවාඩු බංගලා',
                'url' => 'https://alt.army.lk/signaller/holiday-bungalows',
                'category' => 'resources',
                'sort_order' => 12,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'Research and Development Project',
                'title_si' => 'පර්යේෂණ හා සංවර්ධන ව්‍යාපෘතිය',
                'url' => 'https://alt.army.lk/signaller/research-and-development-project',
                'category' => 'resources',
                'sort_order' => 13,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'Veteran Affairs Section',
                'title_si' => 'විශ්‍රාමික රණවිරු කටයුතු අංශය',
                'url' => 'https://alt.army.lk/slsc_va/',
                'category' => 'community',
                'sort_order' => 14,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'History Book Launching Ceremony',
                'title_si' => 'ඉතිහාස ග්‍රන්ථය එළිදැක්වීමේ උත්සවය',
                'url' => 'https://alt.army.lk/signaller/history-book-launching-ceremony',
                'category' => 'heritage',
                'sort_order' => 15,
                'is_active' => true,
                'open_in_new_tab' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('special_programs')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_programs');
    }
};

