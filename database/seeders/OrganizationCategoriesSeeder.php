<?php

namespace Database\Seeders;

use App\Models\OrganizationCategory;
use Illuminate\Database\Seeder;

final class OrganizationCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'headquarters',
                'name_en' => 'Headquarters',
                'name_si' => 'ප්‍රධාන මූලස්ථානය',
                'name_ta' => 'தலைமையகம்',
                'description_en' => 'Strategic command and corps-level administration.',
                'description_si' => 'උපායමාර්ගික අණදීම සහ බලකා මට්ටමේ පරිපාලනය.',
                'description_ta' => 'மூலோபாய கட்டளை மற்றும் படைப்பிரிவு நிர்வாகம்.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'slug' => 'directorates',
                'name_en' => 'Directorates',
                'name_si' => 'අධ්‍යක්ෂක මණ්ඩල',
                'name_ta' => 'பணிப்பகங்கள்',
                'description_en' => 'Functional and technical directorates under Army HQ and Signal Corps.',
                'description_si' => 'යුද්ධ හමුදා මූලස්ථානය යටතේ ක්‍රියාත්මක වන තාක්ෂණික හා මෙහෙයුම් අධ්‍යක්ෂ මණ්ඩල.',
                'description_ta' => 'தொழில்நுட்ப மற்றும் செயல்பாட்டு பணிப்பகங்கள்.',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'slug' => 'brigades',
                'name_en' => 'Brigades',
                'name_si' => 'සංඥා බලසේනා',
                'name_ta' => 'பிரிகேடுகள்',
                'description_en' => 'Operational signal brigades providing strategic and tactical communications.',
                'description_si' => 'උපායමාර්ගික හා මෙහෙයුම් සන්නිවේදනය සපයන සංඥා බලසේනා.',
                'description_ta' => 'செயல்பாட்டு சமிக்ஞை பிரிகேடுகள்.',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'slug' => 'regimental-centre',
                'name_en' => 'Regimental Centre',
                'name_si' => 'රෙජිමේන්තු මධ්‍යස්ථානය',
                'name_ta' => 'படைப்பிரிவு மையம்',
                'description_en' => 'Core regimental administration, traditions, welfare and personnel management.',
                'description_si' => 'රෙජිමේන්තු මූලස්ථාන පරිපාලනය, සුබසාධනය හා සම්ප්‍රදායන්.',
                'description_ta' => 'படைப்பிரிவு மைய நிர்வாகம் மற்றும் நலன்புரி.',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'slug' => 'regiments',
                'name_en' => 'Regiments',
                'name_si' => 'රෙජිමේන්තු',
                'name_ta' => 'ரெஜிமென்ட்டுகள்',
                'description_en' => 'Regular and volunteer signal regiments deployed across military formations.',
                'description_si' => 'ක්ෂේත්‍ර හමුදා ආකෘති පුරා ස්ථාපිත නිත්‍ය සහ ස්වේච්ඡා සංඥා රෙජිමේන්තු.',
                'description_ta' => 'வழக்கமான மற்றும் தன்னார்வ சமிஞ்ஞை ரெஜிமென்ட்டுகள்.',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'slug' => 'training-establishments',
                'name_en' => 'Training Establishments',
                'name_si' => 'පුහුණු ආයතන',
                'name_ta' => 'பயிற்சி நிறுவனங்கள்',
                'description_en' => 'Specialized academies and schools delivering signal and IT instruction.',
                'description_si' => 'සන්නිවේදන, තොරතුරු තාක්ෂණ හා යුද ක්ෂේත්‍ර පුහුණු ආයතන.',
                'description_ta' => 'சமிக்ஞை மற்றும் தகவல் தொழில்நுட்ப பயிற்சி நிறுவனங்கள்.',
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $data) {
            OrganizationCategory::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}

