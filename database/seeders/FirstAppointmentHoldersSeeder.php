<?php

namespace Database\Seeders;

use App\Models\FirstAppointmentHolder;
use Illuminate\Database\Seeder;

final class FirstAppointmentHoldersSeeder extends Seeder
{
    public function run(): void
    {
        $holders = [
            [
                'appointment_en' => '1st Regimental Commander',
                'appointment_si' => '1 වන රෙජිමේන්තු අණදෙන නිලධාරී',
                'name_en' => 'Col CJ Abeyrathne',
                'name_si' => 'කර්නල් සීජේ අබේරත්න',
                'period_en' => '1988',
                'period_si' => '1988',
                'image_path' => 'themes/slsc/assets/images/first-appointments/1st_regimental_commander.jpg',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'appointment_en' => '1st Colonel Commandant',
                'appointment_si' => '1 වන බලකාධිපති',
                'name_en' => 'Brig AMCW Senevirathna psc',
                'name_si' => 'බ්‍රිගේඩියර් ඒඑම්සීඩබ්ලිව් සෙනෙවිරත්න psc',
                'period_en' => '1997',
                'period_si' => '1997',
                'image_path' => 'themes/slsc/assets/images/first-appointments/1st_colonel_commandant.jpg',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'appointment_en' => '1st Signal Brigade Commander / Chief Signal Officer',
                'appointment_si' => '1 වන සංඥා බලසේනාධිපති / ප්‍රධාන සංඥා නිලධාරී',
                'name_en' => 'Brig CJ Abeyrathne',
                'name_si' => 'බ්‍රිගේඩියර් සීජේ අබේරත්න',
                'period_en' => '1988',
                'period_si' => '1988',
                'image_path' => 'themes/slsc/assets/images/first-appointments/1st_signal_brigade_commander.jpg',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'appointment_en' => '1st Chief Signal Officer',
                'appointment_si' => '1 වන ප්‍රධාන සංඥා නිලධාරී',
                'name_en' => 'Brig TF Meedin RSP Ldmc',
                'name_si' => 'බ්‍රිගේඩියර් ටීඑෆ් මීඩින් RSP Ldmc',
                'period_en' => null,
                'period_si' => null,
                'image_path' => 'themes/slsc/assets/images/first-appointments/1st_chief_signal_officer.jpg',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'appointment_en' => '1st Centre Commandant',
                'appointment_si' => '1 වන මධ්‍යස්ථාන සේනාවිධායක',
                'name_en' => 'Col KA Gnanaweera',
                'name_si' => 'කර්නල් කේඒ ඥානවීර',
                'period_en' => null,
                'period_si' => null,
                'image_path' => 'themes/slsc/assets/images/first-appointments/1st_centre_commandant.jpg',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'appointment_en' => '1st CO 1 SLSC',
                'appointment_si' => '1 වන අණදෙන නිලධාරී - 1 ශ්‍රීලංසंब',
                'name_en' => 'Lt Col DV Brohier',
                'name_si' => 'ලුතිනන් කර්නල් ඩීවී බ්‍රෝහියර්',
                'period_en' => null,
                'period_si' => null,
                'image_path' => 'themes/slsc/assets/images/first-appointments/1st_co_1_slsc.jpg',
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($holders as $data) {
            FirstAppointmentHolder::updateOrCreate(
                ['appointment_en' => $data['appointment_en']],
                $data
            );
        }
    }
}

