<?php

use App\Models\SchoolLeader;
use App\Models\SchoolLeadershipPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');
    config()->set('awcms.theme_locales.slsc', ['en', 'si']);
});

function publicSchoolLeader(string $role, string $name, array $attributes = []): SchoolLeader
{
    $position = SchoolLeadershipPosition::query()->where('key', $role)->firstOrFail();

    return SchoolLeader::query()->create(array_merge([
        'position_id' => $position->id,
        'role_key' => $role,
        'title_en' => $position->name_en,
        'name_en' => $name,
        'rank' => 'colonel',
        'start_date' => '2025-06-10',
        'is_active' => true,
        'profile_enabled' => true,
        'profile_html_en' => '<p>English biography</p>',
        'profile_html_si' => '<p>සිංහල චරිතාපදානය</p>',
    ], $attributes));
}

it('features the current commandant and links to their profile from the homepage', function (): void {
    $commandant = publicSchoolLeader('commandant', 'Current Commandant');
    publicSchoolLeader('commandant', 'Former Commandant Officer', ['end_date' => '2024-06-10']);
    publicSchoolLeader('commandant', 'Inactive Commandant', ['is_active' => false]);
    publicSchoolLeader('chief_instructor', 'Current Chief Instructor');

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText('Current Commandant')
        ->assertSeeText('Read More')
        ->assertSee($commandant->profileUrlForLocale('en'))
        ->assertSeeText('View School Leadership')
        ->assertSee(route('school-leadership.index'))
        ->assertDontSeeText('Former Commandant Officer')
        ->assertDontSeeText('Inactive Commandant')
        ->assertDontSeeText('Current Chief Instructor');
});

it('shows active current school appointments including newly added types', function (): void {
    publicSchoolLeader('commandant', 'Excluded Commandant');
    publicSchoolLeader('chief_instructor', 'Current Chief Instructor');
    publicSchoolLeader('adjutant', 'Current Adjutant');
    publicSchoolLeader('warrant_officer', 'Current Warrant Officer');
    publicSchoolLeader('adjutant', 'Former Adjutant', ['end_date' => '2024-06-10']);
    publicSchoolLeader('chief_instructor', 'Inactive Instructor', ['is_active' => false]);

    $position = SchoolLeadershipPosition::query()->create([
        'key' => 'training_officer',
        'name_en' => 'Training Officer',
        'name_si' => 'පුහුණු නිලධාරී',
        'rank_group' => 'commissioned',
        'is_active' => true,
        'show_on_home' => true,
        'sort_order' => 5,
    ]);
    publicSchoolLeader($position->key, 'Current Training Officer');

    $this->get(route('school-leadership.index'))
        ->assertOk()
        ->assertViewHas('leaders', fn ($leaders) => $leaders->count() === 4)
        ->assertSeeTextInOrder(['Current Chief Instructor', 'Current Adjutant', 'Current Warrant Officer', 'Current Training Officer'])
        ->assertSeeText('The Chief Instructor')
        ->assertSeeText('Adjutant')
        ->assertSeeText('Warrant Officer School of Signals')
        ->assertDontSeeText('Excluded Commandant')
        ->assertDontSeeText('Former Adjutant')
        ->assertDontSeeText('Inactive Instructor');
});

it('opens the current commandant profile and preserves the selected language', function (): void {
    $commandant = publicSchoolLeader('commandant', 'Current Commandant', ['name_si' => 'වත්මන් සේනාවිධායක']);
    publicSchoolLeader('commandant', 'Former Commandant Officer', ['end_date' => '2024-06-10']);

    $this->get(route('school-leadership.commandant'))
        ->assertOk()
        ->assertSeeText('Current Commandant')
        ->assertSeeText('Colonel')
        ->assertSeeText('10 Jun 2025')
        ->assertDontSeeText('Former Commandant Officer');

    $this->get(route('home.localized', ['locale' => 'si']))
        ->assertOk()
        ->assertSeeText('වත්මන් සේනාවිධායක')
        ->assertSee($commandant->profileUrlForLocale('si'));

    $this->get(route('school-leadership.commandant.localized', ['locale' => 'si']))
        ->assertOk()
        ->assertSeeText('වත්මන් සේනාවිධායක')
        ->assertSeeText('සේනාවිධායකගේ පැතිකඩ')
        ->assertSee($commandant->profileUrlForLocale('en'))
        ->assertSee(route('school-leadership.localized', ['locale' => 'si']));
});

it('keeps the leadership link available when the commandant is hidden on the homepage', function (): void {
    publicSchoolLeader('commandant', 'Hidden Homepage Commandant');
    SchoolLeadershipPosition::query()->where('key', 'commandant')->update(['show_on_home' => false]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSeeText('Hidden Homepage Commandant')
        ->assertSeeText('View School Leadership');

    $this->get(route('school-leadership.commandant'))
        ->assertOk()
        ->assertSeeText('Hidden Homepage Commandant');
});

it('does not expose inactive appointments or unsupported profile languages', function (): void {
    publicSchoolLeader('commandant', 'Inactive Position Commandant');
    publicSchoolLeader('adjutant', 'Inactive Position Adjutant');
    SchoolLeadershipPosition::query()->whereIn('key', ['commandant', 'adjutant'])->update(['is_active' => false]);

    $this->get(route('school-leadership.commandant'))->assertNotFound();
    $this->get('/ta/school-leadership/commandant')->assertNotFound();
    $this->get(route('school-leadership.index'))
        ->assertOk()
        ->assertDontSeeText('Inactive Position Adjutant');
});
