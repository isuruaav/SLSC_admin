<?php

use App\Livewire\Admin\Courses\CourseIndex;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');
});

it('seeds the supplied courses and only lists active courses with their details', function (): void {
    expect(Course::query()->count())->toBe(16)
        ->and(Course::query()->where('name', 'Certificate in Electronic Warfare')->exists())->toBeTrue()
        ->and(Course::query()->where('name', 'Certificate in Electronic Warfare')->value('name_si'))
        ->toBe('විද්‍යුත් සංග්‍රාම ක්‍රමවේද පිළිබඳ සහතිකපත්‍ර පාඨමාලාව')
        ->and((new MenuItem(['type' => 'route', 'route_name' => 'courses.index']))->resolvedUrl('si'))
        ->toBe(route('courses.index.localized', ['locale' => 'si']));

    $active = Course::query()->create([
        'name' => 'Active course for testing',
        'start_date' => '2026-01-10',
        'end_date' => '2026-02-10',
        'is_active' => true,
        'sort_order' => 0,
    ]);
    $active->qualifications()->create(['qualification' => 'Must have completed basic training', 'sort_order' => 0]);
    Course::query()->create([
        'name' => 'Inactive course for testing',
        'is_active' => false,
        'sort_order' => 1,
    ]);

    $this->get(route('courses.index'))
        ->assertOk()
        ->assertSeeText('Active course for testing')
        ->assertSeeText('Must have completed basic training')
        ->assertSeeText('10 Jan 2026')
        ->assertDontSeeText('Inactive course for testing')
        ->assertSee('href="'.route('courses.index').'"', false);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('courses.index').'"', false);

    $this->get(route('courses.index.localized', ['locale' => 'si']))
        ->assertOk()
        ->assertSeeText('පාඨමාලා')
        ->assertSeeText('Active course for testing')
        ->assertSeeText('විද්‍යුත් සංග්‍රාම ක්‍රමවේද පිළිබඳ සහතිකපත්‍ර පාඨමාලාව')
        ->assertDontSeeText('Certificate in Electronic Warfare');
});

it('allows authorized administrators to create, update, activate and delete courses', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $administrator = User::factory()->create();
    $administrator->assignRole('Super Administrator');

    $this->actingAs($administrator)
        ->get(route('admin.courses.index'))
        ->assertOk()
        ->assertSeeText('Courses')
        ->assertSeeText('Browse courses')
        ->assertSeeText('ICT & Computing')
        ->assertSee('fa-graduation-cap', false)
        ->assertSee('fontawesome-7/css/all.min.css', false);

    Livewire::test(CourseIndex::class)
        ->call('create')
        ->set('name', 'Leadership development')
        ->set('nameSi', 'නායකත්ව සංවර්ධන පාඨමාලාව')
        ->set('courseCategoryId', (string) CourseCategory::query()->where('name', 'Leadership & Management')->value('id'))
        ->set('startDate', '2026-04-01')
        ->set('endDate', '2026-04-20')
        ->set('isActive', true)
        ->set('qualifications', ['Completed intermediate leadership training', ''])
        ->call('save')
        ->assertHasNoErrors();

    $course = Course::query()->where('name', 'Leadership development')->firstOrFail();
    expect($course->start_date?->toDateString())->toBe('2026-04-01')
        ->and($course->end_date?->toDateString())->toBe('2026-04-20')
        ->and($course->name_si)->toBe('නායකත්ව සංවර්ධන පාඨමාලාව')
        ->and($course->category->name)->toBe('Leadership & Management')
        ->and($course->qualifications()->pluck('qualification')->all())->toBe(['Completed intermediate leadership training']);

    Livewire::test(CourseIndex::class)
        ->call('edit', $course->id)
        ->set('name', 'Updated leadership development')
        ->set('nameSi', 'යාවත්කාලීන කළ නායකත්ව සංවර්ධන පාඨමාලාව')
        ->set('qualifications', ['New prerequisite'])
        ->call('save')
        ->assertHasNoErrors();

    $course->refresh();
    expect($course->name)->toBe('Updated leadership development')
        ->and($course->name_si)->toBe('යාවත්කාලීන කළ නායකත්ව සංවර්ධන පාඨමාලාව')
        ->and($course->qualifications()->pluck('qualification')->all())->toBe(['New prerequisite']);

    Livewire::test(CourseIndex::class)->call('toggleActive', $course->id);
    expect($course->fresh()->is_active)->toBeFalse();

    Livewire::test(CourseIndex::class)->call('delete', $course->id);
    expect(Course::query()->find($course->id))->toBeNull();
});

it('lets administrators create, edit and remove categories while keeping assigned courses', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $administrator = User::factory()->create();
    $administrator->assignRole('Super Administrator');
    $categoryId = CourseCategory::query()->where('name', 'Signals & Field Communications')->value('id');
    $course = Course::query()->where('course_category_id', $categoryId)->firstOrFail();

    Livewire::actingAs($administrator)
        ->test(CourseIndex::class)
        ->call('openCategoryForm')
        ->set('categoryName', 'Specialist Training')
        ->set('categoryNameSi', 'විශේෂිත පුහුණුව')
        ->call('saveCategory')
        ->assertHasNoErrors();

    $newCategory = CourseCategory::query()->where('name', 'Specialist Training')->firstOrFail();
    Livewire::actingAs($administrator)
        ->test(CourseIndex::class)
        ->call('openCategoryForm', $newCategory->id)
        ->set('categoryName', 'Advanced Specialist Training')
        ->set('categoryNameSi', 'උසස් විශේෂිත පුහුණුව')
        ->call('saveCategory')
        ->assertHasNoErrors();
    expect($newCategory->fresh()->name)->toBe('Advanced Specialist Training');

    Livewire::actingAs($administrator)->test(CourseIndex::class)->call('deleteCategory', $categoryId);
    expect($course->fresh()->course_category_id)->toBeNull();
});

it('rejects course end dates before start dates and restricts course management', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $administrator = User::factory()->create();
    $administrator->assignRole('Super Administrator');

    $this->get(route('admin.courses.index'))->assertRedirect(route('login'));

    Livewire::actingAs($administrator)
        ->test(CourseIndex::class)
        ->call('create')
        ->set('name', 'Invalid dates')
        ->set('nameSi', 'අවලංගු දින')
        ->set('startDate', '2026-05-20')
        ->set('endDate', '2026-05-01')
        ->call('save')
        ->assertHasErrors(['endDate']);

    $unprivilegedUser = User::factory()->create();
    $this->actingAs($unprivilegedUser)->get(route('admin.courses.index'))->assertForbidden();
});
