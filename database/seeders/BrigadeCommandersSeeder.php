<?php

namespace Database\Seeders;

use App\Models\BrigadeCommander;
use Illuminate\Database\Seeder;

final class BrigadeCommandersSeeder extends Seeder
{
    public function run(): void
    {
        $commanders = [
            [
                'order_number' => '01',
                'name_en' => 'Brig CJ Abeyrathne',
                'name_si' => 'බ්‍රිගේඩියර් සීජේ අබේරත්න',
                'start_date' => '1988-07-15',
                'end_date' => '1991-04-01',
                'period_en' => '1988 - 07 - 15 to 1991 - 04 - 01',
                'period_si' => '1988 - 07 - 15 සිට 1991 - 04 - 01 දක්වා',
                'secondary_tenure_en' => null,
                'secondary_tenure_si' => null,
                'image_path' => 'themes/slsc/assets/images/brigade-commanders/01_brig_cj_abeyrathne.jpg',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'order_number' => '02',
                'name_en' => 'Brig FCJ de silva',
                'name_si' => 'බ්‍රිගේඩියර් එෆ්සීජේ ද සිල්වා',
                'start_date' => '1991-04-01',
                'end_date' => '1991-10-15',
                'period_en' => '1991 - 04 - 01 to 1991 - 10 - 15',
                'period_si' => '1991 - 04 - 01 සිට 1991 - 10 - 15 දක්වා',
                'secondary_tenure_en' => null,
                'secondary_tenure_si' => null,
                'image_path' => 'themes/slsc/assets/images/brigade-commanders/02_brig_fcj_de_silva.jpg',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'order_number' => '03',
                'name_en' => 'Brig CJ Abeyrathne',
                'name_si' => 'බ්‍රිගේඩියර් සීජේ අබේරත්න',
                'start_date' => '1991-10-15',
                'end_date' => '1992-05-01',
                'period_en' => '1991 - 10 - 15 to 1992 - 05 - 01',
                'period_si' => '1991 - 10 - 15 සිට 1992 - 05 - 01 දක්වා',
                'secondary_tenure_en' => null,
                'secondary_tenure_si' => null,
                'image_path' => 'themes/slsc/assets/images/brigade-commanders/03_brig_cj_abeyrathne.jpg',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'order_number' => '04',
                'name_en' => 'Col KA Gnanaweera',
                'name_si' => 'කර්නල් කේඒ ඥානවීර',
                'start_date' => '1992-05-01',
                'end_date' => '1992-10-01',
                'period_en' => '1992 - 05 - 01 to 1992 - 10 - 01',
                'period_si' => '1992 - 05 - 01 සිට 1992 - 10 - 01 දක්වා',
                'secondary_tenure_en' => "Brig KA Gnanaweera\n1992 - 10 - 01 to 1993 - 06 - 27",
                'secondary_tenure_si' => "බ්‍රිගේඩියර් කේඒ ඥානවීර\n1992 - 10 - 01 සිට 1993 - 06 - 27 දක්වා",
                'image_path' => 'themes/slsc/assets/images/brigade-commanders/04_col_ka_gnanaweera.jpg',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'order_number' => '05',
                'name_en' => 'Col HGN Padmasiri',
                'name_si' => 'කර්නල් එච්ජීඑන් පද්මසිරි',
                'start_date' => '1993-06-27',
                'end_date' => '1993-12-01',
                'period_en' => '1993 - 06 - 27 to 1993 - 12 - 01',
                'period_si' => '1993 - 06 - 27 සිට 1993 - 12 - 01 දක්වා',
                'secondary_tenure_en' => null,
                'secondary_tenure_si' => null,
                'image_path' => 'themes/slsc/assets/images/brigade-commanders/05_col_hgn_padmasiri.jpg',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'order_number' => '06',
                'name_en' => 'Brig AMCWB Senevirathna psc',
                'name_si' => 'බ්‍රිගේඩියර් ඒඑම්සීඩබ්ලිව්බී සෙනෙවිරත්න psc',
                'start_date' => '1993-12-01',
                'end_date' => '1994-05-01',
                'period_en' => '1993 - 12 - 01 to 1994 - 05 - 01',
                'period_si' => '1993 - 12 - 01 සිට 1994 - 05 - 01 දක්වා',
                'secondary_tenure_en' => null,
                'secondary_tenure_si' => null,
                'image_path' => 'themes/slsc/assets/images/brigade-commanders/06_brig_amcwb_senevirathna.jpg',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'order_number' => '07',
                'name_en' => 'Col HGN Padmasiri',
                'name_si' => 'කර්නල් එච්ජීඑන් පද්මසිරි',
                'start_date' => '1994-05-01',
                'end_date' => '1996-09-01',
                'period_en' => '1994 - 05 - 01 to 1996 - 09 - 01',
                'period_si' => '1994 - 05 - 01 සිට 1996 - 09 - 01 දක්වා',
                'secondary_tenure_en' => null,
                'secondary_tenure_si' => null,
                'image_path' => 'themes/slsc/assets/images/brigade-commanders/07_col_hgn_padmasiri.jpg',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'order_number' => '08',
                'name_en' => 'Col MA Wipulaguna',
                'name_si' => 'කර්නල් එම්ඒ විපුලගුණ',
                'start_date' => '1996-10-20',
                'end_date' => '1996-12-01',
                'period_en' => '1996 - 10 - 20 to 1996 - 12 - 01',
                'period_si' => '1996 - 10 - 20 සිට 1996 - 12 - 01 දක්වා',
                'secondary_tenure_en' => null,
                'secondary_tenure_si' => null,
                'image_path' => 'themes/slsc/assets/images/brigade-commanders/08_col_ma_wipulaguna.jpg',
                'sort_order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($commanders as $data) {
            BrigadeCommander::updateOrCreate(
                [
                    'sort_order' => $data['sort_order'],
                    'name_en' => $data['name_en'],
                ],
                $data
            );
        }
    }
}

