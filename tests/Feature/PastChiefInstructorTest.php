<?php

use App\Models\PastChiefInstructor;
use App\Models\SchoolLeader;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');
    config()->set('awcms.theme_locales.slsc', ['en', 'si']);
});

it('renders past chief instructors in both public languages', function (): void {
    SchoolLeader::query()->create([
        'role_key' => SchoolLeader::ROLE_CHIEF_INSTRUCTOR,
        'title_en' => 'The Chief Instructor',
        'title_si' => 'ප්‍රධාන උපදේශක',
        'name_en' => 'Present Chief Instructor Silva',
        'name_si' => 'වර්තමාන ප්‍රධාන උපදේශක සිල්වා',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    PastChiefInstructor::query()->create([
        'name_en' => 'Major Chief Instructor Perera',
        'name_si' => 'ප්‍රධාන උපදේශක පෙරේරා',
        'from_date' => '1993-12-27',
        'to_date' => '1994-09-14',
    ]);

    $this->get('/en/history/past-chief-instructors')
        ->assertOk()
        ->assertSeeText('Past Chief Instructors')
        ->assertSeeText('Present Chief Instructor')
        ->assertSeeText('Present Chief Instructor Silva')
        ->assertSeeText('Major Chief Instructor Perera');

    $this->get('/si/history/past-chief-instructors')
        ->assertOk()
        ->assertSeeText('හිටපු ප්‍රධාන උපදේශකවරු')
        ->assertSeeText('වර්තමාන ප්‍රධාන උපදේශක')
        ->assertSeeText('වර්තමාන ප්‍රධාන උපදේශක සිල්වා')
        ->assertSeeText('ප්‍රධාන උපදේශක පෙරේරා');
});
