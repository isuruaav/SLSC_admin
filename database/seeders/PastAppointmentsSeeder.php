<?php

namespace Database\Seeders;

use App\Models\SchoolLeader;
use App\Models\SchoolLeadershipPosition;
use Illuminate\Database\Seeder;

final class PastAppointmentsSeeder extends Seeder
{
    public function run(): void
    {
        $bde1Position = SchoolLeadershipPosition::query()->where('key', 'commander_1_signal_brigade')->first();
        if ($bde1Position) {
            $commanders = [
                [
                    'name_en' => 'Brig CJ Abeyrathne',
                    'name_si' => 'බ්‍රිගේඩියර් සීජේ අබේරත්න',
                    'rank' => 'brigadier',
                    'start_date' => '1988-07-15',
                    'end_date' => '1991-04-01',
                    'image_path' => 'themes/slsc/assets/images/brigade-commanders/01_brig_cj_abeyrathne.jpg',
                    'sort_order' => 1,
                ],
                [
                    'name_en' => 'Brig FCJ de silva',
                    'name_si' => 'බ්‍රිගේඩියර් එෆ්සීජේ ද සිල්වා',
                    'rank' => 'brigadier',
                    'start_date' => '1991-04-01',
                    'end_date' => '1991-10-15',
                    'image_path' => 'themes/slsc/assets/images/brigade-commanders/02_brig_fcj_de_silva.jpg',
                    'sort_order' => 2,
                ],
                [
                    'name_en' => 'Brig CJ Abeyrathne',
                    'name_si' => 'බ්‍රිගේඩියර් සීජේ අබේරත්න',
                    'rank' => 'brigadier',
                    'start_date' => '1991-10-15',
                    'end_date' => '1992-05-01',
                    'image_path' => 'themes/slsc/assets/images/brigade-commanders/03_brig_cj_abeyrathne.jpg',
                    'sort_order' => 3,
                ],
                [
                    'name_en' => 'Col KA Gnanaweera',
                    'name_si' => 'කර්නල් කේඒ ඥානවීර',
                    'rank' => 'colonel',
                    'start_date' => '1992-05-01',
                    'end_date' => '1992-10-01',
                    'secondary_tenure_en' => "Brig KA Gnanaweera\n1992 - 10 - 01 to 1993 - 06 - 27",
                    'secondary_tenure_si' => "බ්‍රිගේඩියර් කේඒ ඥානවීර\n1992 - 10 - 01 සිට 1993 - 06 - 27 දක්වා",
                    'image_path' => 'themes/slsc/assets/images/brigade-commanders/04_col_ka_gnanaweera.jpg',
                    'sort_order' => 4,
                ],
                [
                    'name_en' => 'Col HGN Padmasiri',
                    'name_si' => 'කර්නල් එච්ජීඑන් පද්මසිරි',
                    'rank' => 'colonel',
                    'start_date' => '1993-06-27',
                    'end_date' => '1993-12-01',
                    'image_path' => 'themes/slsc/assets/images/brigade-commanders/05_col_hgn_padmasiri.jpg',
                    'sort_order' => 5,
                ],
                [
                    'name_en' => 'Brig AMCWB Senevirathna psc',
                    'name_si' => 'බ්‍රිගේඩියර් ඒඑම්සීඩබ්ලිව්බී සෙනෙවිරත්න psc',
                    'rank' => 'brigadier',
                    'start_date' => '1993-12-01',
                    'end_date' => '1994-05-01',
                    'image_path' => 'themes/slsc/assets/images/brigade-commanders/06_brig_amcwb_senevirathna.jpg',
                    'sort_order' => 6,
                ],
                [
                    'name_en' => 'Col HGN Padmasiri',
                    'name_si' => 'කර්නල් එච්ජීඑන් පද්මසිරි',
                    'rank' => 'colonel',
                    'start_date' => '1994-05-01',
                    'end_date' => '1996-09-01',
                    'image_path' => 'themes/slsc/assets/images/brigade-commanders/07_col_hgn_padmasiri.jpg',
                    'sort_order' => 7,
                ],
                [
                    'name_en' => 'Col MA Wipulaguna',
                    'name_si' => 'කර්නල් එම්ඒ විපුලගුණ',
                    'rank' => 'colonel',
                    'start_date' => '1996-10-20',
                    'end_date' => '1996-12-01',
                    'image_path' => 'themes/slsc/assets/images/brigade-commanders/08_col_ma_wipulaguna.jpg',
                    'sort_order' => 8,
                ],
            ];

            foreach ($commanders as $cmd) {
                SchoolLeader::query()->updateOrCreate(
                    [
                        'position_id' => $bde1Position->id,
                        'name_en' => $cmd['name_en'],
                        'start_date' => $cmd['start_date'],
                    ],
                    [
                        'role_key' => 'commander_1_signal_brigade',
                        'title_en' => $bde1Position->name_en,
                        'title_si' => $bde1Position->name_si,
                        'name_si' => $cmd['name_si'],
                        'rank' => $cmd['rank'],
                        'end_date' => $cmd['end_date'],
                        'secondary_tenure_en' => $cmd['secondary_tenure_en'] ?? null,
                        'secondary_tenure_si' => $cmd['secondary_tenure_si'] ?? null,
                        'image_path' => $cmd['image_path'],
                        'sort_order' => $cmd['sort_order'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}

