<?php

use App\Livewire\Admin\SchoolLeaders\SchoolLeaderIndex;
use App\Livewire\Admin\SchoolLeaders\SchoolLeaderProfiles;
use App\Livewire\Admin\SchoolLeaders\SchoolLeadershipPositionIndex;
use App\Models\AuditLog;
use App\Models\SchoolLeader;
use App\Models\SchoolLeadershipPosition;
use App\Models\User;
use App\Services\LeadershipProfileContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    config()->set('awcms.active_theme', 'slsc');
    config()->set('awcms.theme_locales.slsc', ['en', 'si']);
});

function profileEditorActor(bool $canUpdate = true): User
{
    $actor = User::factory()->create(['is_active' => true]);
    $actor->givePermissionTo(Permission::findOrCreate('school-leaders.view', 'web'));
    if ($canUpdate) {
        $actor->givePermissionTo(Permission::findOrCreate('school-leaders.update', 'web'));
    }

    return $actor;
}

function editableProfileLeader(string $role = 'commandant', array $attributes = []): SchoolLeader
{
    $position = SchoolLeadershipPosition::query()->where('key', $role)->firstOrFail();

    return SchoolLeader::query()->create(array_merge([
        'position_id' => $position->id,
        'role_key' => $role,
        'title_en' => $position->name_en,
        'name_en' => 'Profile Officer '.$role,
        'name_si' => 'නිලධාරියා',
        'rank' => $role === 'warrant_officer' ? 'warrant_officer_class_i' : 'colonel',
        'start_date' => '2025-06-10',
        'is_active' => true,
    ], $attributes));
}

it('saves bilingual HTML and CSS and compiles Tailwind classes added in the editor', function (): void {
    $leader = editableProfileLeader();
    $component = Livewire::actingAs(profileEditorActor())->test(SchoolLeaderProfiles::class)
        ->set('profileHtmlEn', '<section class="md:grid-cols-2 text-[31px] career"><h2>English Career</h2><p>Service history</p></section>')
        ->set('profileHtmlSi', '<section class="p-6"><h2>සිංහල චරිතාපදානය</h2></section>')
        ->set('profileCss', '.career { color: var(--blue-700); }')
        ->set('profileEnabled', true)
        ->call('save')
        ->assertHasNoErrors();

    $leader->refresh();
    expect($leader->profile_enabled)->toBeTrue()
        ->and($leader->profile_html_en)->toContain('English Career')
        ->and($leader->profile_html_si)->toContain('සිංහල චරිතාපදානය')
        ->and($leader->profile_compiled_css)->toContain('31px', 'grid-template-columns')
        ->and($leader->profile_css)->toContain('var(--blue-700)')
        ->and(AuditLog::query()->where('event', 'school-leader.profile-updated')->exists())->toBeTrue();

    $component->set('profileEnabled', false)->call('save')->assertHasNoErrors();
    expect($leader->refresh()->profile_enabled)->toBeFalse()
        ->and($leader->profile_html_en)->toContain('English Career')
        ->and($leader->profile_html_si)->toContain('සිංහල චරිතාපදානය');
});

it('opens the complete profile editor under the administration layout', function (): void {
    $leader = editableProfileLeader();
    $actor = profileEditorActor();
    $actor->givePermissionTo(Permission::findOrCreate('admin.access', 'web'));

    $this->actingAs($actor)
        ->get(route('admin.school-leadership-profiles.index', ['leader' => $leader->id]))
        ->assertOk()
        ->assertSeeText('Leadership Profiles')
        ->assertSeeText('HTML + Tailwind CSS')
        ->assertSeeText('Enable public profile')
        ->assertSeeText('සිංහල')
        ->assertSeeText('Save Profile');
});

it('requires meaningful content in both languages before enabling', function (): void {
    $leader = editableProfileLeader();
    Livewire::actingAs(profileEditorActor())->test(SchoolLeaderProfiles::class)
        ->set('profileHtmlEn', '<p>English biography</p>')
        ->set('profileHtmlSi', '<script>alert(1)</script>')
        ->set('profileEnabled', true)
        ->call('save')->assertHasErrors('profileHtmlSi');

    expect($leader->refresh()->profile_enabled)->toBeFalse();
});

it('previews unsaved edits without publishing them', function (): void {
    $leader = editableProfileLeader();
    Livewire::actingAs(profileEditorActor())->test(SchoolLeaderProfiles::class)
        ->set('profileHtmlEn', '<p class="text-blue-700">Unsaved biography</p>')
        ->call('preview')->assertHasNoErrors()
        ->assertSet('previewEn', fn ($value) => str_contains($value, 'Unsaved biography'));

    expect($leader->refresh()->profile_html_en)->toBeNull()
        ->and($leader->profile_enabled)->toBeFalse();
});

it('rejects editing actions for viewers and users without leadership access', function (): void {
    $leader = editableProfileLeader();
    Livewire::actingAs(profileEditorActor(false))->test(SchoolLeaderProfiles::class)
        ->assertDontSeeHtml('wire:submit="save"')
        ->call('save')->assertForbidden();

    Livewire::actingAs(User::factory()->create())->test(SchoolLeaderProfiles::class)->assertForbidden();
    expect($leader->refresh()->profile_enabled)->toBeFalse();
});

it('keeps separate content when switching appointment holders', function (): void {
    editableProfileLeader('commandant', ['profile_html_en' => '<p>Commandant biography</p>']);
    $adjutant = editableProfileLeader('adjutant', ['profile_html_en' => '<p>Adjutant biography</p>']);

    Livewire::actingAs(profileEditorActor())->test(SchoolLeaderProfiles::class)
        ->assertSet('profileHtmlEn', '<p>Commandant biography</p>')
        ->set('selectedLeaderId', (string) $adjutant->id)
        ->assertSet('profileHtmlEn', '<p>Adjutant biography</p>')
        ->assertSet('editingId', $adjutant->id);
});

it('offers active present and past appointments including custom types in the profile editor', function (): void {
    foreach (SchoolLeader::allowedRoles() as $role) {
        editableProfileLeader($role);
    }
    SchoolLeadershipPosition::query()->create([
        'key' => 'training_officer', 'name_en' => 'Training Officer', 'name_si' => 'පුහුණු නිලධාරී',
        'rank_group' => 'commissioned', 'is_active' => true, 'sort_order' => 5,
    ]);
    editableProfileLeader('training_officer');
    editableProfileLeader('chief_instructor', ['name_en' => 'Former Instructor', 'end_date' => '2026-01-01']);
    editableProfileLeader('adjutant', ['name_en' => 'Inactive Adjutant', 'is_active' => false]);

    Livewire::actingAs(profileEditorActor())->test(SchoolLeaderProfiles::class)
        ->assertViewHas('leaders', fn ($leaders) => $leaders->count() === 6)
        ->assertSeeText('Training Officer')
        ->assertSeeText('Former Instructor')
        ->assertDontSeeText('Inactive Adjutant');
});

it('offers profile creation and editing for past appointments', function (): void {
    $former = editableProfileLeader('commandant', ['name_en' => 'Former Officer', 'end_date' => '2026-01-01']);
    $actor = profileEditorActor();
    $actor->givePermissionTo(Permission::findOrCreate('admin.access', 'web'));

    $this->actingAs($actor)->get(route('admin.school-leaders.index'))
        ->assertOk()->assertSeeText('Former Officer')
        ->assertSee('href="'.route('admin.school-leadership-profiles.index', ['leader' => $former->id]).'"', false);
    $this->get(route('admin.school-leadership-profiles.index', ['leader' => $former->id]))->assertOk();

    Livewire::actingAs($actor)->test(SchoolLeaderProfiles::class)
        ->assertSet('editingId', $former->id)
        ->set('profileHtmlEn', '<p>Former officer biography</p>')
        ->set('profileHtmlSi', '<p>හිටපු නිලධාරියාගේ චරිතාපදානය</p>')
        ->set('profileEnabled', true)
        ->call('save')->assertHasNoErrors();

    $this->get($former->refresh()->profileUrlForLocale('en'))->assertOk()
        ->assertSee('Former officer biography')->assertSeeText('Past Commandant');
    $this->get($former->profileUrlForLocale('si'))->assertOk()->assertSee('හිටපු නිලධාරියාගේ චරිතාපදානය');
});

it('keeps an open profile editor usable when an appointment becomes past', function (): void {
    $leader = editableProfileLeader();
    $actor = profileEditorActor();
    $saveComponent = Livewire::actingAs($actor)->test(SchoolLeaderProfiles::class);
    $previewComponent = Livewire::actingAs($actor)->test(SchoolLeaderProfiles::class);
    $leader->update(['end_date' => '2026-01-01']);

    $saveComponent->set('profileHtmlEn', '<p>Retained edit</p>')->call('save')->assertHasNoErrors();
    $previewComponent->call('preview')->assertHasNoErrors();
    expect($leader->refresh()->profile_html_en)->toBe('<p>Retained edit</p>');
});

it('strips executable HTML and rejects unsafe CSS', function (): void {
    $leader = editableProfileLeader();
    Livewire::actingAs(profileEditorActor())->test(SchoolLeaderProfiles::class)
        ->set('profileHtmlEn', '<p onclick="alert(1)">Biography</p><script>alert(2)</script>')
        ->set('profileHtmlSi', '<p>චරිතාපදානය</p>')
        ->set('profileEnabled', true)
        ->call('save')->assertHasNoErrors()
        ->set('profileCss', '@import "https://example.com/style.css";')
        ->call('save')->assertHasErrors('profileCss');

    expect($leader->refresh()->profile_html_en)->not->toContain('onclick', '<script')
        ->and($leader->profile_css)->toBe('');
});

it('isolates profile styles in a sandboxed document and prevents style tag breakouts', function (): void {
    $document = app(LeadershipProfileContent::class)->document('<p>Biography</p>', 'body{color:red}', '</style><script>alert(1)</script>', 'si');
    expect($document)->toContain('<html lang="si">', 'body{color:red}')
        ->not->toContain('</style><script>');
});

it('publishes enabled profiles for original and custom types and hides disabled pages', function (string $role): void {
    SchoolLeadershipPosition::query()->firstOrCreate(['key' => $role], [
        'name_en' => 'Additional Appointment '.$role,
        'name_si' => 'අමතර පත්වීම',
        'rank_group' => 'commissioned', 'is_active' => true, 'sort_order' => 5,
    ]);
    $leader = editableProfileLeader($role, [
        'profile_enabled' => true,
        'profile_html_en' => '<p>English career for '.$role.'</p>',
        'profile_html_si' => '<p>සිංහල චරිතාපදානය '.$role.'</p>',
    ]);
    $englishUrl = $leader->profileUrlForLocale('en');
    $sinhalaUrl = $leader->profileUrlForLocale('si');
    $this->get($englishUrl)->assertOk()->assertSee('English career for '.$role)->assertDontSee('සිංහල චරිතාපදානය')
        ->assertSee('sandbox="allow-same-origin"', false)->assertSee($sinhalaUrl);
    $this->get($sinhalaUrl)->assertOk()->assertSee('සිංහල චරිතාපදානය')->assertDontSee('English career for '.$role)->assertSee($englishUrl);

    $listing = $role === 'commandant' ? route('home') : route('school-leadership.index');
    $sinhalaListing = $role === 'commandant'
        ? route('home.localized', ['locale' => 'si'])
        : route('school-leadership.localized', ['locale' => 'si']);
    $this->get($listing)->assertOk()->assertSee($englishUrl);
    $this->get($sinhalaListing)->assertOk()->assertSee('href="'.$sinhalaUrl.'"', false);
    $leader->update(['profile_enabled' => false]);
    $this->get($englishUrl)->assertNotFound();
    $this->get($sinhalaUrl)->assertNotFound();
    $this->get($listing)->assertOk()->assertDontSee('href="'.$englishUrl.'"', false);
    $this->get($sinhalaListing)->assertOk()->assertDontSee('href="'.$sinhalaUrl.'"', false);
})->with(['commandant', 'chief_instructor', 'adjutant', 'warrant_officer', 'training_officer', 'training_officer_2']);

it('creates and enables a profile after adding a new present appointment through the admin forms', function (): void {
    $actor = profileEditorActor();
    Livewire::actingAs($actor)->test(SchoolLeadershipPositionIndex::class)
        ->set('nameEn', 'Training Officer')
        ->set('nameSi', 'පුහුණු නිලධාරී')
        ->set('rankGroup', 'commissioned')
        ->set('sortOrder', 5)
        ->call('save')->assertHasNoErrors();

    $position = SchoolLeadershipPosition::query()->where('key', 'training_officer')->firstOrFail();
    Livewire::actingAs($actor)->test(SchoolLeaderIndex::class)
        ->set('roleKey', $position->key)
        ->set('rank', 'captain')
        ->set('nameEn', 'New Training Officer')
        ->set('nameSi', 'නව පුහුණු නිලධාරී')
        ->set('startDate', '2026-10-01')
        ->set('isCurrent', true)
        ->call('save')->assertHasNoErrors();

    $leader = SchoolLeader::query()->where('position_id', $position->id)->firstOrFail();
    $url = route('school-leadership.profile', ['appointment' => 'training-officer']);
    $this->get(route('school-leadership.index'))->assertOk()->assertSeeText('New Training Officer')
        ->assertDontSee('href="'.$url.'"', false);
    $this->get($url)->assertNotFound();

    Livewire::actingAs($actor)->test(SchoolLeaderProfiles::class)
        ->assertSet('editingId', $leader->id)
        ->set('profileHtmlEn', '<p>New appointment biography</p>')
        ->set('profileHtmlSi', '<p>නව පත්වීමේ චරිතාපදානය</p>')
        ->set('profileEnabled', true)
        ->call('save')->assertHasNoErrors();

    $this->get($url)->assertOk()->assertSee('New appointment biography');
    $this->get(route('school-leadership.profile.localized', ['locale' => 'si', 'appointment' => 'training-officer']))
        ->assertOk()->assertSee('නව පත්වීමේ චරිතාපදානය');
    $this->get(route('school-leadership.index'))->assertOk()->assertSee('href="'.$leader->refresh()->profileUrlForLocale('en').'"', false);

    $position->update(['is_active' => false]);
    expect($leader->refresh()->supportsProfile())->toBeFalse();
    $this->get($url)->assertNotFound();
    Livewire::actingAs($actor)->test(SchoolLeaderProfiles::class)->assertSet('editingId', null);
});

it('keeps former profiles available by record while excluding inactive profiles', function (): void {
    $leader = editableProfileLeader('adjutant', ['profile_enabled' => true, 'end_date' => '2026-01-01']);
    $url = route('school-leadership.profile', ['appointment' => 'adjutant']);
    $this->get($url)->assertNotFound();
    $recordUrl = $leader->profileUrlForLocale('en');
    $this->get($recordUrl)->assertOk();
    $leader->update(['end_date' => null, 'is_active' => false]);
    $this->get($url)->assertNotFound();
    $this->get($recordUrl)->assertNotFound();
});

it('preserves a saved profile and its URL when a present appointment ends and is replaced', function (string $role): void {
    SchoolLeadershipPosition::query()->firstOrCreate(['key' => $role], [
        'name_en' => 'Training Officer', 'name_si' => 'පුහුණු නිලධාරී',
        'rank_group' => 'commissioned', 'is_active' => true, 'sort_order' => 5,
    ]);
    $leader = editableProfileLeader($role, [
        'name_en' => 'Archived Officer '.$role,
        'profile_enabled' => true,
        'profile_html_en' => '<p>Archived service biography '.$role.'</p>',
        'profile_html_si' => '<p>හිටපු නිලධාරියාගේ චරිතාපදානය '.$role.'</p>',
        'profile_css' => 'p{color:var(--blue-700)}',
        'profile_compiled_css' => 'p{font-size:17px}',
    ]);
    $fields = ['profile_enabled', 'profile_html_en', 'profile_html_si', 'profile_css', 'profile_compiled_css'];
    $originalProfile = $leader->only($fields);
    $englishUrl = $leader->profileUrlForLocale('en');
    $sinhalaUrl = $leader->profileUrlForLocale('si');
    $actor = profileEditorActor();

    Livewire::actingAs($actor)->test(SchoolLeaderIndex::class)
        ->call('edit', $leader->id)
        ->set('isCurrent', false)
        ->set('endDate', '2026-10-01')
        ->call('save')->assertHasNoErrors();

    $leader->refresh();
    expect($leader->only($fields))->toBe($originalProfile)
        ->and($leader->profileUrlForLocale('en'))->toBe($englishUrl)
        ->and($leader->profileUrlForLocale('si'))->toBe($sinhalaUrl);

    Livewire::actingAs($actor)->test(SchoolLeaderIndex::class)
        ->call('startCreate')
        ->set('roleKey', $role)
        ->set('rank', $leader->rank)
        ->set('nameEn', 'Replacement Officer '.$role)
        ->set('nameSi', 'නව නිලධාරියා')
        ->set('startDate', '2026-10-02')
        ->set('isCurrent', true)
        ->call('save')->assertHasNoErrors();

    $replacement = SchoolLeader::query()->where('position_id', $leader->position_id)->current()->firstOrFail();
    expect($replacement->id)->not->toBe($leader->id)
        ->and($replacement->profile_enabled)->toBeFalse()
        ->and($replacement->profile_html_en)->toBeNull();
    $replacement->update([
        'profile_enabled' => true,
        'profile_html_en' => '<p>Replacement biography</p>',
        'profile_html_si' => '<p>නව නිලධාරියාගේ චරිතාපදානය</p>',
    ]);

    $this->get($englishUrl)->assertOk()->assertSee('Archived service biography '.$role)
        ->assertSeeText('01 Oct 2026')->assertDontSee('Replacement biography');
    $this->get($sinhalaUrl)->assertOk()->assertSee('හිටපු නිලධාරියාගේ චරිතාපදානය '.$role);
    $this->get($replacement->profileUrlForLocale('en'))->assertOk()->assertSee('Replacement biography');
    $this->get(route('school-leadership.history'))->assertOk()
        ->assertSeeText('Archived Officer '.$role)->assertSee('href="'.$englishUrl.'"', false)
        ->assertDontSeeText('Replacement Officer '.$role);
    $this->get(route('school-leadership.history.localized', ['locale' => 'si']))->assertOk()
        ->assertSee('href="'.$sinhalaUrl.'"', false);
})->with(['commandant', 'chief_instructor', 'adjutant', 'warrant_officer', 'training_officer']);

it('hides disabled past profiles while keeping their saved content editable', function (): void {
    $leader = editableProfileLeader('adjutant', [
        'end_date' => '2026-10-01', 'profile_enabled' => false,
        'profile_html_en' => '<p>Saved former biography</p>',
    ]);
    $url = route('school-leadership.show', ['leader' => $leader->id]);
    $this->get($url)->assertNotFound();
    $this->get(route('school-leadership.history'))->assertOk()->assertDontSee('href="'.$url.'"', false);
    Livewire::actingAs(profileEditorActor())->test(SchoolLeaderProfiles::class)
        ->assertSet('editingId', $leader->id)
        ->assertSet('profileHtmlEn', '<p>Saved former biography</p>')
        ->call('preview')->assertHasNoErrors()
        ->assertSet('previewEn', fn ($html) => str_contains($html, 'Saved former biography'));
});

it('links enabled past profiles from the existing appointment history pages', function (string $role, string $historyRoute): void {
    $leader = editableProfileLeader($role, [
        'end_date' => '2026-10-01', 'profile_enabled' => true,
        'profile_html_en' => '<p>Former officer service record</p>',
        'profile_html_si' => '<p>හිටපු නිලධාරියාගේ සේවා වාර්තාව</p>',
    ]);
    $englishUrl = $leader->profileUrlForLocale('en');
    $sinhalaUrl = $leader->profileUrlForLocale('si');
    $this->get(route($historyRoute))->assertOk()->assertSee('href="'.$englishUrl.'"', false);
    $this->get(route($historyRoute.'.localized'))->assertOk()->assertSee('href="'.$sinhalaUrl.'"', false);

    $leader->update(['profile_enabled' => false]);
    $this->get(route($historyRoute))->assertOk()->assertDontSee('href="'.$englishUrl.'"', false);
    $this->get(route($historyRoute.'.localized'))->assertOk()->assertDontSee('href="'.$sinhalaUrl.'"', false);
})->with([
    ['commandant', 'history.past-commandants'],
    ['chief_instructor', 'history.past-chief-instructors'],
]);
