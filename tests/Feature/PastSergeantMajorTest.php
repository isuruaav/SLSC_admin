<?php

use App\Models\SchoolLeader;
use App\Models\SchoolLeadershipPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');
    config()->set('awcms.theme_locales.slsc', ['en', 'si']);
});

function pastSergeantMajorLeader(string $name, array $attributes = []): SchoolLeader
{
    $position = SchoolLeadershipPosition::query()
        ->where('key', SchoolLeader::ROLE_WARRANT_OFFICER)
        ->firstOrFail();

    return SchoolLeader::query()->create(array_merge([
        'position_id' => $position->id,
        'role_key' => SchoolLeader::ROLE_WARRANT_OFFICER,
        'title_en' => $position->name_en,
        'title_si' => $position->name_si,
        'name_en' => $name,
        'name_si' => 'සැරයන් මේජර් '.$name,
        'rank' => 'warrant_officer_class_i',
        'start_date' => '2025-01-01',
        'is_active' => true,
    ], $attributes));
}

it('renders sergeant major history chronologically with the current holder last', function (string $locale): void {
    $current = pastSergeantMajorLeader('Current Holder');
    $later = pastSergeantMajorLeader('Later Holder', ['start_date' => '2020-01-01', 'end_date' => '2024-12-31']);
    $earlier = pastSergeantMajorLeader('First Holder', ['start_date' => '2015-01-01', 'end_date' => '2019-12-31']);
    $route = $locale === 'si' ? 'history.past-sgt-major.localized' : 'history.past-sgt-major';

    $this->get(route($route))->assertOk()
        ->assertViewHas('currentLocale', $locale)
        ->assertViewHas('sergeantMajors', fn ($leaders): bool => $leaders->modelKeys() === [$earlier->id, $later->id, $current->id])
        ->assertSeeTextInOrder($locale === 'si'
            ? ['සැරයන් මේජර් First Holder', 'සැරයන් මේජර් Later Holder', 'සැරයන් මේජර් Current Holder']
            : ['First Holder', 'Later Holder', 'Current Holder'])
        ->assertSeeText('01.01.2015')->assertSeeText('31.12.2019')
        ->assertSeeText($locale === 'si' ? 'වර්තමාන රෙජිමේන්තු සැරයන් මේජර්' : 'Present Regimental Sergeant Major')
        ->assertSeeText($locale === 'si' ? 'වෝරන්ට් නිලධාරී පන්තිය I' : 'Warrant Officer Class I')
        ->assertSeeText($locale === 'si' ? 'රූපය නොමැත' : 'Image unavailable')
        ->assertSee('href="'.route('history.past-sgt-major').'"', false)
        ->assertSee('href="'.route('history.past-sgt-major.localized').'"', false)
        ->assertSee('<link rel="canonical" href="'.route($route).'">', false);
})->with(['en', 'si']);

it('selects this appointment and legacy records while hiding inactive and unrelated leaders', function (): void {
    $assigned = pastSergeantMajorLeader('Assigned Holder', ['role_key' => SchoolLeader::ROLE_CHIEF_INSTRUCTOR]);
    $legacy = pastSergeantMajorLeader('Legacy Holder', ['position_id' => null]);
    pastSergeantMajorLeader('Hidden Holder', ['is_active' => false]);
    $otherPosition = SchoolLeadershipPosition::query()->where('key', SchoolLeader::ROLE_CHIEF_INSTRUCTOR)->firstOrFail();
    pastSergeantMajorLeader('Unrelated Holder', ['position_id' => $otherPosition->id]);

    $this->get(route('history.past-sgt-major'))->assertOk()
        ->assertViewHas('sergeantMajors', fn ($leaders): bool => $leaders->modelKeys() === [$assigned->id, $legacy->id])
        ->assertSeeText('Assigned Holder')->assertSeeText('Legacy Holder')
        ->assertDontSeeText('Hidden Holder')->assertDontSeeText('Unrelated Holder');
});

it('hides records belonging to an inactive appointment', function (): void {
    pastSergeantMajorLeader('Disabled Appointment Holder');
    SchoolLeadershipPosition::query()->where('key', SchoolLeader::ROLE_WARRANT_OFFICER)->update(['is_active' => false]);

    $this->get(route('history.past-sgt-major'))->assertOk()
        ->assertViewHas('sergeantMajors', fn ($leaders): bool => $leaders->isEmpty())
        ->assertDontSeeText('Disabled Appointment Holder');
});

it('shows an empty state and links the new page from the fallback navigation', function (string $locale): void {
    $route = $locale === 'si' ? 'history.past-sgt-major.localized' : 'history.past-sgt-major';
    $this->get(route($route))->assertOk()
        ->assertSeeText($locale === 'si'
            ? 'රෙජිමේන්තු සැරයන් මේජර්වරුන්ගේ තොරතුරු තවම ඇතුළත් කර නැත.'
            : 'Regimental Sergeant Major records have not been added yet.');

    $homeUrl = $locale === 'si' ? route('home.localized', ['locale' => 'si']) : route('home');
    $this->get($homeUrl)->assertOk()->assertSee('href="'.route($route).'"', false);
})->with(['en', 'si']);

it('links to enabled profiles in the selected language and keeps disabled profiles hidden', function (string $locale): void {
    $leader = pastSergeantMajorLeader('Profile Holder', [
        'end_date' => '2025-12-31',
        'profile_enabled' => true,
        'profile_html_en' => '<p>Past service record.</p>',
        'profile_html_si' => '<p>හිටපු නිලධාරියාගේ සේවා වාර්තාව.</p>',
    ]);
    $historyUrl = route($locale === 'si' ? 'history.past-sgt-major.localized' : 'history.past-sgt-major');
    $profileUrl = $leader->profileUrlForLocale($locale);

    $this->get($historyUrl)->assertOk()->assertSee('href="'.$profileUrl.'"', false);
    $this->get($profileUrl)->assertOk()->assertSee($locale === 'si'
        ? 'හිටපු නිලධාරියාගේ සේවා වාර්තාව.' : 'Past service record.');
    $leader->update(['profile_enabled' => false]);
    $this->get($historyUrl)->assertOk()->assertDontSee('href="'.$profileUrl.'"', false);
})->with(['en', 'si']);
