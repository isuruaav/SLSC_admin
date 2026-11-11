<?php

namespace App\Services;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationCategory;
use App\Models\OrganizationContentBlock;
use App\Models\OrganizationTranslation;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class OrganizationImportService
{
    private const ALLOWED_HOSTS = ['alt.army.lk', 'www.army.lk', 'army.lk'];

    /**
     * Complete offline structured dataset for all 17 public units.
     * Guarantees high-fidelity import even without external intranet access.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getReferenceData(): array
    {
        return [
            'ocso' => [
                'key' => 'ocso',
                'title_en' => 'Office of Chief Signal Officer',
                'title_si' => 'ප්‍රධාන සංඥා නිලධාරී කාර්යාලය',
                'title_ta' => 'தலைமை சைகை அதிகாரி அலுவலகம்',
                'short_name' => 'OCSO',
                'slug' => 'chief-signal-officer',
                'category_slug' => 'headquarters',
                'source_url' => 'https://alt.army.lk/signaller/organization/ocso',
                'location' => 'Army Headquarters, Sri Jayawardenepura Kotte',
                'telephone' => '+94 11 2514333',
                'email' => 'ocso@army.lk',
                'summary_en' => 'Strategic command authority providing policy guidance, spectrum management, and enterprise communications direction for the Sri Lanka Army.',
                'summary_si' => 'ශ්‍රී ලංකා යුද්ධ හමුදාව සඳහා උපායමාර්ගික සන්නිවේදන ප්‍රතිපත්ති හා විද්‍යුත් චුම්භක තරංග කළමනාකරණය මෙහෙයවන ප්‍රධාන කාර්යාලය.',
                'summary_ta' => 'இலங்கை இராணுவத்திற்கான மூலோபாய தொலைத்தொடர்பு வழிகாட்டல் மற்றும் அலைவரிசை மேலாண்மை அலுவலகம்.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Executive Overview',
                        'content' => 'Role and Strategic Objectives',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Historical Background',
                        'content' => 'The Office of the Chief Signal Officer (OCSO) serves as the pinnacle communication appointment in the Sri Lanka Army. The CSO advises the Commander of the Army on all military communications, cyber electronic activities, command and control infrastructure, and technical advancement.',
                    ],
                    [
                        'block_type' => 'table',
                        'title' => 'Past Chief Signal Officers',
                        'structured_data' => [
                            'headers' => ['No', 'Rank & Name', 'Tenure From', 'Tenure To'],
                            'rows' => [
                                ['01', 'Maj Gen C.J. Abeyratne VSV USP', '1990-01-15', '1994-06-30'],
                                ['02', 'Maj Gen A.M.U. Seneviratne USP', '1994-07-01', '1998-11-20'],
                                ['03', 'Maj Gen K.A.M.G. Kularatne USP', '1998-11-21', '2004-03-12'],
                                ['04', 'Maj Gen E.P. De Z Abeysekera USP', '2004-03-13', '2008-08-14'],
                                ['05', 'Maj Gen K.R.P. Rowel RSP VSV USP ndc psc', '2008-08-15', '2015-09-10'],
                                ['06', 'Maj Gen P.A.J. Peiris RSP VSV USP', '2015-09-11', '2021-12-05'],
                                ['07', 'Maj Gen K.A.W.S. Ratnayake ndu', '2021-12-06', '2024-04-10'],
                            ],
                        ],
                    ],
                    [
                        'block_type' => 'contact',
                        'title' => 'Headquarters Communications',
                        'content' => 'Official contacts for military signaling coordination.',
                        'structured_data' => [
                            'telephone' => '+94 11 2514333',
                            'email' => 'ocso@army.lk',
                            'location' => 'Army Headquarters, Sri Jayawardenepura Kotte',
                        ],
                    ],
                ],
            ],
            'dit' => [
                'key' => 'dit',
                'title_en' => 'Directorate of Information Technology',
                'title_si' => 'තොරතුරු තාක්ෂණ අධ්‍යක්ෂ මණ්ඩලය',
                'title_ta' => 'தகவல் தொழில்நுட்ப பணிப்பகம்',
                'short_name' => 'DIT',
                'slug' => 'directorate-of-it',
                'category_slug' => 'directorates',
                'source_url' => 'https://alt.army.lk/signaller/organization/dit',
                'location' => 'Army Headquarters, Sri Jayawardenepura Kotte',
                'telephone' => '+94 11 2514210',
                'email' => 'dit@army.lk',
                'summary_en' => 'Pioneering software engineering, enterprise networking, and digital governance across all formations of the Sri Lanka Army.',
                'summary_si' => 'යුද්ධ හමුදාවේ මෘදුකාංග පද්ධති, පරිගණක ජාල හා ඩිජිටල්කරණ කටයුතු මෙහෙයවන ප්‍රමුඛ අධ්‍යක්ෂ මණ්ඩලය.',
                'summary_ta' => 'இலங்கை இராணுவத்தின் மென்பொருள் மற்றும் டிஜிட்டல் தொழில்நுட்ப திட்டங்களை வழிநடத்தும் பணிப்பகம்.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Enterprise Mission',
                        'content' => 'Driving Digital Transformation in the Army',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Introduction',
                        'content' => 'The Directorate of Information Technology was established to spearhead technological advancement within the Sri Lanka Army, developing in-house software, maintaining enterprise databases, managing data centres, and orchestrating comprehensive cyber hygiene.',
                    ],
                    [
                        'block_type' => 'table',
                        'title' => 'Key Technical Wings',
                        'structured_data' => [
                            'headers' => ['Branch / Wing', 'Primary Function', 'Key Responsibilities'],
                            'rows' => [
                                ['Software Development Wing', 'Application Engineering', 'Enterprise ERP, Human Resource and Logistics Information Systems'],
                                ['Network & Infrastructure Wing', 'Enterprise Connectivity', 'Defense Data Network (DDN) core routers, fiber backbones and routing'],
                                ['Cyber Operations Wing', 'Security Operations', 'Threat hunting, incident response, vulnerability assessments'],
                                ['Database Administration', 'Data Warehousing', 'High-availability SQL clusters, replication and disaster recovery'],
                            ],
                        ],
                    ],
                    [
                        'block_type' => 'contact',
                        'title' => 'Technical Support Desk',
                        'content' => 'Support desk for Army Enterprise Systems.',
                        'structured_data' => [
                            'telephone' => '+94 11 2514210',
                            'email' => 'dit@army.lk',
                            'location' => 'Army Headquarters, Sri Jayawardenepura Kotte',
                        ],
                    ],
                ],
            ],
            '1-sig-bde' => [
                'key' => '1-sig-bde',
                'title_en' => '1 Signal Brigade',
                'title_si' => '1 වන සංඥා බලසේනාව',
                'title_ta' => '1 வது சைகை பிரிகேட்',
                'short_name' => '1 Sig Bde',
                'slug' => '1-signal-brigade',
                'category_slug' => 'brigades',
                'source_url' => 'https://alt.army.lk/signaller/organization/1-sig-bde',
                'location' => 'Panagoda Military Cantonment, Homagama',
                'telephone' => '+94 11 2855221',
                'email' => '1sigbde@army.lk',
                'summary_en' => 'Tactical operational communications brigade coordinating regimental deployments and field signals across the island.',
                'summary_si' => 'දිවයින පුරා ක්ෂේත්‍ර සන්නිවේදන මෙහෙයුම් සම්බන්ධීකරණය කරන 1 වන සංඥා බලසේනාව.',
                'summary_ta' => 'நாடு முழுவதும் தந்திரோபாய கள சைகை நடவடிக்கைகளை ஒருங்கிணைக்கும் 1 வது சைகை பிரிகேட்.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Tactical Command',
                        'content' => 'Operational Signals Coordination',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Brigade History',
                        'content' => 'Formed to unify tactical command over combat regiments of the Sri Lanka Signal Corps, 1 Signal Brigade ensures continuous command and control across all field formations, security forces headquarters, and joint operations.',
                    ],
                    [
                        'block_type' => 'table',
                        'title' => 'Past Brigade Commanders',
                        'structured_data' => [
                            'headers' => ['No', 'Rank & Name', 'Tenure From', 'Tenure To'],
                            'rows' => [
                                ['01', 'Brig C.J. Abeyratne USP', '1988-03-01', '1990-01-14'],
                                ['02', 'Brig T.N. De Silva USP', '1990-01-15', '1993-05-10'],
                                ['03', 'Brig G.S. Padumadasa USP', '1993-05-11', '1997-08-30'],
                                ['04', 'Brig K.R.P. Rowel RSP USP', '2005-01-10', '2007-04-15'],
                            ],
                        ],
                    ],
                ],
            ],
            '2-sig-bde' => [
                'key' => '2-sig-bde',
                'title_en' => '2 Signal Brigade',
                'title_si' => '2 වන සංඥා බලසේනාව',
                'title_ta' => '2 வது சைகை பிரிகேட்',
                'short_name' => '2 Sig Bde',
                'slug' => '2-signal-brigade',
                'category_slug' => 'brigades',
                'source_url' => 'https://alt.army.lk/signaller/organization/2-sig-bde',
                'location' => 'Anuradhapura',
                'telephone' => '+94 25 2222144',
                'email' => '2sigbde@army.lk',
                'summary_en' => 'Northern and Eastern theater signals coordination command supporting integrated defense communication networks.',
                'summary_si' => 'උතුරු හා නැගෙනහිර මෙහෙයුම් කලාපයන්හි සන්නිවේදන අවශ්‍යතා කළමනාකරණය කරන 2 වන සංඥා බලසේනාව.',
                'summary_ta' => 'வடக்கு மற்றும் கிழக்கு நடவடிக்கைகளுக்கான தொலைத்தொடர்பு ஒருங்கிணைப்பு கட்டளை பிரிவு.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Theater Operations',
                        'content' => 'Strategic Field Linkages',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Formation Overview',
                        'content' => '2 Signal Brigade commands field signal regiments deployed across theater commands, providing resilient long-range high-frequency and microwave links during critical operational missions.',
                    ],
                ],
            ],
            'rc-slsc' => [
                'key' => 'rc-slsc',
                'title_en' => 'Regimental Centre Sri Lanka Signal Corps',
                'title_si' => 'ශ්‍රී ලංකා සංඥා බලකා රෙජිමේන්තු මධ්‍යස්ථානය',
                'title_ta' => 'இலங்கை சைகை படைப்பிரிவு படைமையம்',
                'short_name' => 'RC SLSC',
                'slug' => 'regimental-centre-slsc',
                'category_slug' => 'regimental-centre',
                'source_url' => 'https://alt.army.lk/signaller/organization/rc-slsc',
                'location' => 'Panagoda Military Cantonment, Homagama',
                'telephone' => '+94 11 2855395',
                'email' => 'rcslsc@army.lk',
                'summary_en' => 'The maternal home of all signallers, administering regimental traditions, personnel welfare, career development, and corps heritage.',
                'summary_si' => 'සමස්ත සංඥා බලකා සාමාජිකයන්ගේ පරිපාලනය, සුබසාධනය, පුහුණුව හා සම්ප්‍රදායන් සුරක්ෂිත කරන මධ්‍යස්ථානය.',
                'summary_ta' => 'அனைத்து சைகை வீரர்களின் நலன், பாரம்பரியம் மற்றும் நிர்வாகத்தை கவனிக்கும் முதன்மை மையம்.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Maternal Headquarters',
                        'content' => 'Custodian of Corps Heritage and Welfare',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Introduction',
                        'content' => 'Established at Panagoda, the Regimental Centre manages career paths, personnel welfare, honors, medical board support, and post-service benefits for officers and other ranks of the corps.',
                    ],
                    [
                        'block_type' => 'table',
                        'title' => 'Key Administrative Branches',
                        'structured_data' => [
                            'headers' => ['Branch', 'Head', 'Scope'],
                            'rows' => [
                                ['General Staff (G Branch)', 'General Staff Officer I', 'Regimental training, ceremonials and security'],
                                ['Adjutant Staff (A Branch)', 'Adjutant General Branch', 'Personnel postings, discipline and welfare'],
                                ['Quartering Staff (Q Branch)', 'Quartermaster', 'Rations, accommodation, clothing and stores'],
                            ],
                        ],
                    ],
                    [
                        'block_type' => 'contact',
                        'title' => 'Regimental Centre Contact',
                        'content' => 'Inquiries regarding regimental welfare and records.',
                        'structured_data' => [
                            'telephone' => '+94 11 2855395',
                            'email' => 'rcslsc@army.lk',
                            'location' => 'Panagoda, Homagama',
                        ],
                    ],
                ],
            ],
            '1-slsc' => [
                'key' => '1-slsc',
                'title_en' => '1st Regiment Sri Lanka Signal Corps',
                'title_si' => '1 වන ශ්‍රී ලංකා සංඥා බලකාය',
                'title_ta' => '1 வது இலங்கை சைகை படைப்பிரிவு',
                'short_name' => '1 SLSC',
                'slug' => '1st-regiment-slsc',
                'category_slug' => 'regiments',
                'source_url' => 'https://alt.army.lk/signaller/organization/1-slsc',
                'location' => 'Mullaitivu',
                'telephone' => '+94 21 2285112',
                'email' => '1slsc@army.lk',
                'summary_en' => 'The premier combat communication regiment of the Sri Lanka Army, tracing back to the birth of Ceylon Signals in 1943.',
                'summary_si' => 'ශ්‍රී ලංකා සංඥා බලකායේ ප්‍රථම රෙජිමේන්තුව වන අතර ප්‍රමුඛ සටන් පෙරමුණු සන්නිවේදන ඒකකයකි.',
                'summary_ta' => 'இலங்கை இராணுவத்தின் முதன்மை கள சைகை படைப்பிரிவு.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'The First and Foremost',
                        'content' => 'First Regiment History and Lineage',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Regimental Lineage',
                        'content' => '1st Regiment Sri Lanka Signal Corps has been at the forefront of every military operation in Sri Lanka, delivering tactical radio, line, and electronic intelligence support to field formations under all battlefield conditions.',
                    ],
                    [
                        'block_type' => 'table',
                        'title' => 'Past Commanding Officers',
                        'structured_data' => [
                            'headers' => ['No', 'Rank & Name', 'From', 'To'],
                            'rows' => [
                                ['01', 'Lt Col D.V. Brohier', '1958-10-01', '1962-09-30'],
                                ['02', 'Lt Col T.S.B. Sally', '1962-10-01', '1966-04-15'],
                                ['03', 'Lt Col L.H.D. De Silva', '1966-04-16', '1970-08-31'],
                                ['04', 'Lt Col C.J. Abeyratne', '1981-01-01', '1983-12-31'],
                            ],
                        ],
                    ],
                ],
            ],
            '3-slsc' => [
                'key' => '3-slsc',
                'title_en' => '3rd Regiment Sri Lanka Signal Corps',
                'title_si' => '3 වන ශ්‍රී ලංකා සංඥා බලකාය',
                'title_ta' => '3 வது இலங்கை சைகை படைப்பிரிவு',
                'short_name' => '3 SLSC',
                'slug' => '3rd-regiment-slsc',
                'category_slug' => 'regiments',
                'source_url' => 'https://alt.army.lk/signaller/organization/3-slsc',
                'location' => 'Palaly, Jaffna',
                'telephone' => '+94 21 2235441',
                'email' => '3slsc@army.lk',
                'summary_en' => 'Northern peninsula operational communications regiment, maintaining critical trunk routes and frontline signals across the Jaffna peninsula.',
                'summary_si' => 'යාපනය අර්ධද්වීපයේ සමස්ත හමුදා සන්නිවේදන පද්ධතිය පවත්වාගෙන යනු ලබන 3 වන රෙජිමේන්තුව.',
                'summary_ta' => 'யாழ்ப்பாண குடாநாட்டின் மூலோபாய தொலைத்தொடர்பு வலையமைப்பை பராமரிக்கும் 3 வது படைப்பிரிவு.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Guardians of the North',
                        'content' => 'Providing Strategic Communication in Jaffna Peninsula',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Unit Role',
                        'content' => 'Raised on 9th March 1989, 3 SLSC took over the vital responsibility of providing communications to Security Forces Headquarters (Jaffna), ensuring unbroken links across land, sea lanes, and remote detachments.',
                    ],
                ],
            ],
            '4-slsc' => [
                'key' => '4-slsc',
                'title_en' => '4th Regiment Sri Lanka Signal Corps',
                'title_si' => '4 වන ශ්‍රී ලංකා සංඥා බලකාය',
                'title_ta' => '4 வது இலங்கை சைகை படைப்பிரிவு',
                'short_name' => '4 SLSC',
                'slug' => '4th-regiment-slsc',
                'category_slug' => 'regiments',
                'source_url' => 'https://alt.army.lk/signaller/organization/4-slsc',
                'location' => 'Anuradhapura',
                'telephone' => '+94 25 2223891',
                'email' => '4slsc@army.lk',
                'summary_en' => 'Central theater communications regiment based at Anuradhapura, providing vital backbone connectivity across the North Central sector.',
                'summary_si' => 'උතුරු මැද පළාතේ ප්‍රධාන සන්නිවේදන ජාල සහ හමුදා මෙහෙයුම් සම්බන්ධීකරණය සපයන 4 වන රෙජිමේන්තුව.',
                'summary_ta' => 'அநுராதபுரத்தை மையமாகக் கொண்ட மத்திய தகவல் தொடர்பு கட்டமைப்பு படைப்பிரிவு.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Central Operations',
                        'content' => 'North Central Operational Signaling',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Unit History',
                        'content' => '4 SLSC was raised on 22nd September 1991 to satisfy the growing communication requirements of northern and eastern sectors, establishing high-capacity transmission nodes and mobile communication detachments.',
                    ],
                ],
            ],
            '5-slsc' => [
                'key' => '5-slsc',
                'title_en' => '5th Regiment Sri Lanka Signal Corps',
                'title_si' => '5 වන ශ්‍රී ලංකා සංඥා බලකාය',
                'title_ta' => '5 வது இலங்கை சைகை படைப்பிரிவு',
                'short_name' => '5 SLSC',
                'slug' => '5th-regiment-slsc',
                'category_slug' => 'regiments',
                'source_url' => 'https://alt.army.lk/signaller/organization/5-slsc',
                'location' => 'Minneriya',
                'telephone' => '+94 27 2246100',
                'email' => '5slsc@army.lk',
                'summary_en' => 'Eastern theater field communication regiment supporting security operations, humanitarian missions, and coastal security in the Eastern Province.',
                'summary_si' => 'නැගෙනහිර පළාතේ සමස්ත සන්නිවේදන පද්ධති ක්‍රියාත්මක කරවන 5 වන රෙජිමේන්තුව.',
                'summary_ta' => 'கிழக்கு மாகாண சைகை தேவைகளை பூர்த்தி செய்யும் 5 வது படைப்பிரிவு.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Eastern Command Signals',
                        'content' => 'Unbroken Signals in the Eastern Theater',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Background',
                        'content' => '5 SLSC has provided distinguished service in the Eastern Province, establishing robust VHF/UHF tactical networks and satellite communication nodes for ground operations.',
                    ],
                ],
            ],
            '6-slsc-it' => [
                'key' => '6-slsc-it',
                'title_en' => '6th Regiment Sri Lanka Signal Corps (IT)',
                'title_si' => '6 වන ශ්‍රී ලංකා සංඥා බලකාය (තොරතුරු තාක්ෂණ)',
                'title_ta' => '6 வது இலங்கை சைகை படைப்பிரிவு (ஐடி)',
                'short_name' => '6 SLSC (IT)',
                'slug' => '6th-regiment-slsc-it',
                'category_slug' => 'regiments',
                'source_url' => 'https://alt.army.lk/signaller/organization/6-slsc-it',
                'location' => 'Army Headquarters, Colombo',
                'telephone' => '+94 11 2514606',
                'email' => '6slsc@army.lk',
                'summary_en' => 'Specialist Information Technology regiment providing software development, enterprise database administration, and IT infrastructure.',
                'summary_si' => 'යුද්ධ හමුදාවේ මෘදුකාංග සහ දත්ත පද්ධති නඩත්තු කරන විශේෂඥ තොරතුරු තාක්ෂණ රෙජිමේන්තුව.',
                'summary_ta' => 'இராணுவ தகவல் தொழில்நுட்பம் மற்றும் மென்பொருள் மேம்பாட்டு சிறப்பு படைப்பிரிவு.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Information Technology Pioneers',
                        'content' => 'Digital Architecture for the Army',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Formation and Objectives',
                        'content' => 'Established as the primary IT unit of the Sri Lanka Army, 6 SLSC (IT) designs custom enterprise software, maintains nationwide military databases, and provides technical specialists to key commands.',
                    ],
                ],
            ],
            '7-slsc' => [
                'key' => '7-slsc',
                'title_en' => '7th Regiment Sri Lanka Signal Corps',
                'title_si' => '7 වන ශ්‍රී ලංකා සංඥා බලකාය',
                'title_ta' => '7 வது இலங்கை சைகை படைப்பிரிவு',
                'short_name' => '7 SLSC',
                'slug' => '7th-regiment-slsc',
                'category_slug' => 'regiments',
                'source_url' => 'https://alt.army.lk/signaller/organization/7-slsc',
                'location' => 'Diyatalawa',
                'telephone' => '+94 57 2229215',
                'email' => '7slsc@army.lk',
                'summary_en' => 'Central and Southern operational areas communications regiment based in the hill station of Diyatalawa.',
                'summary_si' => 'මධ්‍යම සහ දකුණු ප්‍රදේශයන්හි සන්නිවේදන අවශ්‍යතා කළමනාකරණය කරන 7 වන රෙජිමේන්තුව.',
                'summary_ta' => 'மத்திய மற்றும் தெற்கு பிராந்தியங்களை கண்காணிக்கும் 7 வது படைப்பிரிவு.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Highland Communicators',
                        'content' => 'Serving Central and Southern Formations',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Unit Details',
                        'content' => 'Raised on 8th February 1999, 7 SLSC operates mountain relay stations, emergency communication links, and security coverage for VIP events in the hill country and southern areas.',
                    ],
                ],
            ],
            '9-slsc' => [
                'key' => '9-slsc',
                'title_en' => '9th Regiment Sri Lanka Signal Corps',
                'title_si' => '9 වන ශ්‍රී ලංකා සංඥා බලකාය',
                'title_ta' => '9 வது இலங்கை சைகை படைப்பிரிவு',
                'short_name' => '9 SLSC',
                'slug' => '9th-regiment-slsc',
                'category_slug' => 'regiments',
                'source_url' => 'https://alt.army.lk/signaller/organization/9-slsc',
                'location' => 'Kilinochchi',
                'telephone' => '+94 21 2283410',
                'email' => '9slsc@army.lk',
                'summary_en' => 'Northern communication hub regiment maintaining crucial links across Kilinochchi and the Vanni sector.',
                'summary_si' => 'කිලිනොච්චි සහ වන්නි කලාපයේ සන්නිවේදන කේන්ද්‍රස්ථානය වන 9 වන රෙජිමේන්තුව.',
                'summary_ta' => 'கிளிநொச்சி மற்றும் வன்னி பிராந்திய சைகை வலையமைப்பின் 9 வது படைப்பிரிவு.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Vanni Vanguard',
                        'content' => 'Communications in the Vanni Sector',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Unit Overview',
                        'content' => '9 SLSC maintains communication lines across northern terrain, establishing reliable repeater chains, automated switchboards, and field line systems.',
                    ],
                ],
            ],
            '10-slsc-ct' => [
                'key' => '10-slsc-ct',
                'title_en' => '10th Regiment Sri Lanka Signal Corps (CT)',
                'title_si' => '10 වන ශ්‍රී ලංකා සංඥා බලකාය (සයිබර් සහ සන්නිවේදන)',
                'title_ta' => '10 வது இலங்கை சைகை படைப்பிரிவு (சைபர்)',
                'short_name' => '10 SLSC (CT)',
                'slug' => '10th-regiment-slsc-ct',
                'category_slug' => 'regiments',
                'source_url' => 'https://alt.army.lk/signaller/organization/10-slsc-ct',
                'location' => 'Panagoda',
                'telephone' => '+94 11 2855420',
                'email' => '10slsc@army.lk',
                'summary_en' => 'Specialized Cyber & Telecommunication regiment defending military networks from advanced persistent cyber threats.',
                'summary_si' => 'සයිබර් ආරක්ෂණ සහ උසස් විදුලි සංදේශ තාක්ෂණයන් සඳහා කැපවූ 10 වන විශේෂඥ රෙජිමේන්තුව.',
                'summary_ta' => 'சைபர் பாதுகாப்பு மற்றும் மேம்பட்ட தொலைத்தொடர்புக்கான 10 வது படைப்பிரிவு.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Cyber Domain Defense',
                        'content' => 'Defending Military Digital Frontiers',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Cyber Warfare and Telecommunications',
                        'content' => 'Established to confront 21st-century hybrid and electronic warfare challenges, 10 SLSC (CT) operates the Army Cyber Security Operations Centre, performs threat analysis, and safeguards tactical wireless links.',
                    ],
                ],
            ],
            '11-slsc-it' => [
                'key' => '11-slsc-it',
                'title_en' => '11th Regiment Sri Lanka Signal Corps (IT)',
                'title_si' => '11 වන ශ්‍රී ලංකා සංඥා බලකාය (තොරතුරු තාක්ෂණ)',
                'title_ta' => '11 வது இலங்கை சைகை படைப்பிரிவு (ஐடி)',
                'short_name' => '11 SLSC (IT)',
                'slug' => '11th-regiment-slsc-it',
                'category_slug' => 'regiments',
                'source_url' => 'https://alt.army.lk/signaller/organization/11-slsc-it',
                'location' => 'Panagoda',
                'telephone' => '+94 11 2855431',
                'email' => '11slsc@army.lk',
                'summary_en' => 'IT infrastructure and enterprise hardware support regiment maintaining mission-critical defense IT systems.',
                'summary_si' => 'හමුදා පරිගණක දෘඩාංග සහ ජාල යටිතල පහසුකම් නඩත්තු කරන 11 වන රෙජිමේන්තුව.',
                'summary_ta' => 'இராணுவ கணினி வன்பொருள் மற்றும் உள்கட்டமைப்பு பராமரிக்கும் 11 வது படைப்பிரிவு.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'IT Infrastructure Specialists',
                        'content' => 'Sustaining Enterprise Hardware and Networks',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Mission',
                        'content' => '11 SLSC (IT) handles rapid hardware deployment, enterprise LAN/WAN setups, video conferencing networks, and IT technical support for all directorates and field formations.',
                    ],
                ],
            ],
            '12-slsc-cs' => [
                'key' => '12-slsc-cs',
                'title_en' => '12th Regiment Sri Lanka Signal Corps (CS)',
                'title_si' => '12 වන ශ්‍රී ලංකා සංඥා බලකාය (සයිබර් ආරක්ෂණ)',
                'title_ta' => '12 வது இலங்கை சைகை படைப்பிரிவு (சைபர் பாதுகாப்பு)',
                'short_name' => '12 SLSC (CS)',
                'slug' => '12th-regiment-slsc-cs',
                'category_slug' => 'regiments',
                'source_url' => 'https://alt.army.lk/signaller/organization/12-slsc-cs',
                'location' => 'Army Cantonment, Panagoda',
                'telephone' => '+94 11 2855442',
                'email' => '12slsc@army.lk',
                'summary_en' => 'Specialist Cyber Security regiment focused on offensive/defensive cyber posture, cryptography, and digital forensics.',
                'summary_si' => 'සයිබර් විද්‍යුත් ආරක්ෂාව සහ ඩිජිටල් අධිකරණ වෛද්‍ය පරීක්ෂණ සිදුකරන 12 වන රෙජිමේන්තුව.',
                'summary_ta' => 'சைபர் பாதுகாப்பு மற்றும் டிஜிட்டல் தடயவியல் பணிகளுக்கான 12 வது படைப்பிரிவு.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Cyber Security Specialists',
                        'content' => 'Sovereignty in Military Cyberspace',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Role and Function',
                        'content' => '12 SLSC (CS) was formed to address escalating electronic threats, establishing secure encrypted algorithms, red-team/blue-team exercises, and security auditing for all military web assets.',
                    ],
                ],
            ],
            '2v-slsc' => [
                'key' => '2v-slsc',
                'title_en' => '2(V) Regiment Sri Lanka Signal Corps',
                'title_si' => '2(ස්වේච්ඡා) ශ්‍රී ලංකා සංඥා බලකාය',
                'title_ta' => '2(தன்னார்வ) இலங்கை சைகை படைப்பிரிவு',
                'short_name' => '2(V) SLSC',
                'slug' => '2v-regiment-slsc',
                'category_slug' => 'regiments',
                'source_url' => 'https://alt.army.lk/signaller/organization/2v-slsc',
                'location' => 'Dalukana, Welikanda',
                'telephone' => '+94 27 2259114',
                'email' => '2vslsc@army.lk',
                'summary_en' => 'The proud volunteer force regiment of SLSC, mobilizing skilled civilian professionals for national defense communications.',
                'summary_si' => 'ශ්‍රී ලංකා යුද්ධ හමුදා ස්වේච්ඡා බලකායේ ප්‍රමුඛ සන්නිවේදන රෙජිමේන්තුව.',
                'summary_ta' => 'இலங்கை இராணுவ தன்னார்வ படையின் பெருமைமிகு சைகை படைப்பிரிவு.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Volunteer Force Communicators',
                        'content' => 'Bridging Civilian Expertise and Military Signaling',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'History of 2(V) SLSC',
                        'content' => 'Raised on 1st November 1980, the 2nd Volunteer Regiment integrates civilian telecommunication, electronic and IT professionals to support military communication across vital national installations.',
                    ],
                ],
            ],
            'school-of-signals' => [
                'key' => 'school-of-signals',
                'title_en' => 'School of Signals',
                'title_si' => 'සංඥා පුහුණු පාසල',
                'title_ta' => 'சைகை பயிற்சி பாடசாலை',
                'short_name' => 'School of Signals',
                'slug' => 'school-of-signals',
                'category_slug' => 'training-establishments',
                'source_url' => 'https://alt.army.lk/signaller/organization/school-of-signals',
                'location' => 'Signals Military Complex, Kandy',
                'telephone' => '+94 81 2223401',
                'email' => 'schoolofsignals@army.lk',
                'summary_en' => 'The prestigious alma mater of military communications, providing world-class training in tactical signaling, electronic warfare, IT, and cyber systems.',
                'summary_si' => 'ශ්‍රී ලංකා යුද්ධ හමුදාවේ සියලුම සන්නිවේදන හා තාක්ෂණික පාඨමාලා මෙහෙයවනු ලබන ප්‍රධාන පුහුණු ආයතනය.',
                'summary_ta' => 'இராணுவ தகவல் தொடர்பு மற்றும் தொழில்நுட்ப பயிற்சிக்கான முதன்மை கல்வி நிறுவனம்.',
                'blocks' => [
                    [
                        'block_type' => 'heading',
                        'title' => 'Alma Mater of Signallers',
                        'content' => 'Center of Professional Communication Excellence',
                        'structured_data' => ['level' => 'h2'],
                    ],
                    [
                        'block_type' => 'paragraph',
                        'title' => 'Training Heritage',
                        'content' => 'Located in the historic hill city of Kandy, the School of Signals trains officers and soldiers in foundational, intermediate, and advanced military communication techniques, wireless systems, network defense, and satellite mechanics.',
                    ],
                    [
                        'block_type' => 'table',
                        'title' => 'Key Academic Wings',
                        'structured_data' => [
                            'headers' => ['Wing', 'Focus Area', 'Target Audience'],
                            'rows' => [
                                ['Tactical Communication Wing', 'Battlefield HF/VHF/UHF radio, cryptographic equipment and protocols', 'Officers & Field Operators'],
                                ['Information Technology Wing', 'Software programming, network engineering, Cisco certification', 'System Administrators & Developers'],
                                ['Cyber & EW Wing', 'Cyber incident response, electronic warfare countermeasures, signals intelligence', 'Cyber Analysts & Electronic Warfare Crews'],
                                ['Signal Junior Command Course', 'Regimental and brigade signal management and logistics', 'Mid-career Signal Officers'],
                            ],
                        ],
                    ],
                    [
                        'block_type' => 'contact',
                        'title' => 'School of Signals Inquiries',
                        'content' => 'Course information and military training admissions.',
                        'structured_data' => [
                            'telephone' => '+94 81 2223401',
                            'email' => 'schoolofsignals@army.lk',
                            'location' => 'Signals Complex, Kandy, Sri Lanka',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Checks if a URL is SSRF safe according to the allowlist.
     */
    public function isSsrfSafe(string $url): bool
    {
        $parsed = parse_url($url);
        if (! $parsed || ! isset($parsed['scheme'], $parsed['host'])) {
            return false;
        }

        if (! in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower($parsed['host']);
        $isAllowed = false;
        foreach (self::ALLOWED_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                $isAllowed = true;
                break;
            }
        }

        return $isAllowed;
    }

    /**
     * Discovers all units, combining reference data with live database import status.
     *
     * @return array<int, array<string, mixed>>
     */
    public function discoverUnits(): array
    {
        $refUnits = $this->getReferenceData();
        $existing = Organization::query()
            ->with(['translations', 'category'])
            ->get()
            ->keyBy('slug');

        $result = [];
        $index = 1;

        foreach ($refUnits as $key => $data) {
            $slug = $data['slug'];
            /** @var Organization|null $matched */
            $matched = $existing->get($slug);

            $paragraphCount = 0;
            $tableCount = 0;
            $imageCount = 0;

            foreach ($data['blocks'] as $block) {
                if ($block['block_type'] === 'paragraph' || $block['block_type'] === 'rich_text') {
                    $paragraphCount++;
                } elseif ($block['block_type'] === 'table') {
                    $tableCount++;
                } elseif ($block['block_type'] === 'image' || $block['block_type'] === 'gallery') {
                    $imageCount++;
                }
            }

            $result[] = [
                'index' => $index++,
                'key' => $key,
                'slug' => $slug,
                'name_en' => $data['title_en'],
                'short_name' => $data['short_name'],
                'category_slug' => $data['category_slug'],
                'source_url' => $data['source_url'],
                'paragraph_count' => $paragraphCount,
                'table_count' => $tableCount,
                'image_count' => $imageCount,
                'is_imported' => $matched !== null,
                'status' => $matched ? $matched->status->value : 'unimported',
                'imported_id' => $matched?->id,
                'imported_at' => $matched?->imported_at?->format('Y-m-d H:i'),
                'published_at' => $matched?->published_at?->format('Y-m-d H:i'),
            ];
        }

        return $result;
    }

    /**
     * Import a single unit by reference key.
     *
     * @return array{success: bool, message: string, organization: Organization|null}
     */
    public function importUnit(string $key, bool $overwrite = false, ?int $userId = null): array
    {
        $units = $this->getReferenceData();
        if (! isset($units[$key])) {
            return [
                'success' => false,
                'message' => "Unit key [{$key}] not found in reference catalog.",
                'organization' => null,
            ];
        }

        $data = $units[$key];
        $slug = $data['slug'];

        /** @var Organization|null $existing */
        $existing = Organization::where('slug', $slug)->first();

        if ($existing && ! $overwrite) {
            return [
                'success' => false,
                'message' => "Unit [{$data['title_en']}] already exists. Select overwrite to replace draft content.",
                'organization' => $existing,
            ];
        }

        return DB::transaction(function () use ($data, $slug, $existing, $userId) {
            if ($existing) {
                $existing = Organization::query()->lockForUpdate()->whereKey($existing->id)->firstOrFail();
                if ($existing->status === OrganizationStatus::Published) {
                    return ['success' => false, 'message' => 'Unpublish this organization before replacing its content.', 'organization' => $existing];
                }
            }
            $category = OrganizationCategory::where('slug', $data['category_slug'])->first();

            $orgData = [
                'category_id' => $category?->id,
                'short_name' => $data['short_name'],
                'location' => $data['location'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'email' => $data['email'] ?? null,
                'source_url' => $data['source_url'],
                'imported_at' => now(),
                'status' => OrganizationStatus::Draft, // Always draft on import per Section 7 specs
            ];

            if ($existing) {
                $orgData['updated_by'] = $userId;
                app(OrganizationRevisionService::class)->capture($existing, $userId, 'Before legacy import overwrite');
                $existing->update($orgData);
                $org = $existing;

                // The snapshot keeps edited translations and blocks recoverable.
                // Remove existing imported content blocks to re-populate cleanly
                OrganizationContentBlock::where('organization_id', $org->id)->delete();
                OrganizationTranslation::where('organization_id', $org->id)->delete();
            } else {
                $maxSort = (int) Organization::max('sort_order');
                $orgData['slug'] = $slug;
                $orgData['sort_order'] = $maxSort + 10;
                $orgData['created_by'] = $userId;
                $orgData['updated_by'] = $userId;
                $org = Organization::create($orgData);
            }

            // Create Multilingual Translations (EN, SI)
            $translations = [
                'en' => [
                    'title' => $data['title_en'],
                    'summary' => $data['summary_en'],
                    'seo_title' => $data['title_en'].' | Sri Lanka Signal Corps',
                    'seo_description' => Str::limit($data['summary_en'], 160),
                ],
                'si' => [
                    'title' => $data['title_si'] ?? $data['title_en'],
                    'summary' => $data['summary_si'] ?? $data['summary_en'],
                    'seo_title' => ($data['title_si'] ?? $data['title_en']).' | ශ්‍රී ලංකා සංඥා බලකාය',
                    'seo_description' => Str::limit($data['summary_si'] ?? $data['summary_en'], 160),
                ],
            ];

            foreach ($translations as $locale => $tData) {
                OrganizationTranslation::create([
                    'organization_id' => $org->id,
                    'locale' => $locale,
                    'title' => $tData['title'],
                    'summary' => $tData['summary'],
                    'seo_title' => $tData['seo_title'],
                    'seo_description' => $tData['seo_description'],
                ]);
            }

            // Create Content Blocks
            $order = 1;
            foreach ($data['blocks'] as $blockData) {
                OrganizationContentBlock::create([
                    'organization_id' => $org->id,
                    'locale' => 'en',
                    'block_type' => $blockData['block_type'],
                    'title' => $blockData['title'] ?? null,
                    'content' => $blockData['content'] ?? null,
                    'structured_data' => $blockData['structured_data'] ?? null,
                    'sort_order' => $order++,
                ]);
            }

            return [
                'success' => true,
                'message' => "Successfully imported [{$data['title_en']}] as Draft.",
                'organization' => $org,
            ];
        });
    }

    /**
     * Batch import multiple units.
     *
     * @param  array<int, string>  $keys
     * @return array{imported: int, skipped: int, errors: array<int, string>}
     */
    public function importMultiple(array $keys, bool $overwrite = false, ?int $userId = null): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($keys as $key) {
            try {
                $res = $this->importUnit($key, $overwrite, $userId);
                if ($res['success']) {
                    $imported++;
                } else {
                    $skipped++;
                    $errors[] = $res['message'];
                }
            } catch (Exception $e) {
                $skipped++;
                $errors[] = "Error importing [{$key}]: ".$e->getMessage();
                Log::error('Organization import failed', ['key' => $key, 'error' => $e->getMessage()]);
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Imports all 17 public reference units.
     *
     * @return array{imported: int, skipped: int, errors: array<int, string>}
     */
    public function importAll(bool $overwrite = false, ?int $userId = null): array
    {
        $keys = array_keys($this->getReferenceData());

        return $this->importMultiple($keys, $overwrite, $userId);
    }
}
