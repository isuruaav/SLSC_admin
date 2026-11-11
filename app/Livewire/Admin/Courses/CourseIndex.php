<?php

namespace App\Livewire\Admin\Courses;

use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

final class CourseIndex extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public ?int $selectedCategory = null;

    public bool $formOpen = false;

    public bool $categoryFormOpen = false;

    public ?int $editingCategoryId = null;

    public string $categoryName = '';

    public string $categoryNameSi = '';

    public string $name = '';

    public string $nameSi = '';

    public string $courseCategoryId = '';

    public string $startDate = '';

    public string $endDate = '';

    public bool $isActive = true;

    /** @var array<int, string> */
    public array $qualifications = [''];

    /** @var array<int, string> */
    public array $qualificationsSi = [];

    public string $search = '';

    public string $statusFilter = 'all';

    public function mount(): void
    {
        Gate::authorize('courses.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function selectCategory(?int $categoryId): void
    {
        Gate::authorize('courses.view');
        if ($categoryId !== null) {
            CourseCategory::query()->findOrFail($categoryId);
        }
        $this->selectedCategory = $categoryId;
        $this->resetPage();
    }

    public function openCategoryForm(?int $id = null): void
    {
        Gate::authorize('courses.categories.manage');
        $this->reset(['editingCategoryId', 'categoryName', 'categoryNameSi']);
        $this->resetValidation();

        if ($id !== null) {
            $category = CourseCategory::query()->findOrFail($id);
            $this->editingCategoryId = $category->id;
            $this->categoryName = $category->name;
            $this->categoryNameSi = $category->name_si;
        }

        $this->categoryFormOpen = true;
    }

    public function saveCategory(): void
    {
        Gate::authorize('courses.categories.manage');
        $validated = $this->validate([
            'categoryName' => [
                'required',
                'string',
                'max:100',
                Rule::unique('course_categories', 'name')->ignore($this->editingCategoryId),
            ],
            'categoryNameSi' => ['required', 'string', 'max:100'],
        ]);

        if ($this->editingCategoryId === null) {
            $category = new CourseCategory;
            $category->sort_order = max(0, (int) CourseCategory::query()->max('sort_order')) + 1;
        } else {
            $category = CourseCategory::query()->findOrFail($this->editingCategoryId);
        }

        $category->fill([
            'name' => trim($validated['categoryName']),
            'name_si' => trim($validated['categoryNameSi']),
        ])->save();

        $this->categoryFormOpen = false;
        $this->reset(['editingCategoryId', 'categoryName', 'categoryNameSi']);
        $this->resetValidation();
        session()->flash('status', 'Category saved.');
    }

    public function closeCategoryForm(): void
    {
        Gate::authorize('courses.categories.manage');
        $this->categoryFormOpen = false;
        $this->reset(['editingCategoryId', 'categoryName', 'categoryNameSi']);
        $this->resetValidation();
    }

    public function deleteCategory(int $id): void
    {
        Gate::authorize('courses.categories.manage');
        $category = CourseCategory::query()->findOrFail($id);
        $category->delete();

        if ($this->selectedCategory === $id) {
            $this->selectedCategory = null;
        }

        session()->flash('status', 'Category removed. Its courses are now uncategorized.');
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('courses.create');
        $this->resetForm();
        $this->formOpen = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('courses.update');
        $course = Course::query()->with('qualifications')->findOrFail($id);
        $this->resetForm();
        $this->editingId = $course->id;
        $this->name = $course->name;
        $this->nameSi = $course->name_si ?? '';
        $this->courseCategoryId = $course->course_category_id !== null
            ? (string) $course->course_category_id
            : '';
        $this->startDate = $course->start_date?->format('Y-m-d') ?? '';
        $this->endDate = $course->end_date?->format('Y-m-d') ?? '';
        $this->isActive = $course->is_active;
        $this->qualifications = $course->qualifications->map(fn ($row): string => (string) $row->getAttribute('qualification'))->all();
        $this->qualificationsSi = $course->qualifications->map(fn ($row) => (string) ($row->qualification_si ?? ''))->all();
        $this->qualifications = $this->qualifications !== [] ? $this->qualifications : [''];
        $this->formOpen = true;
    }

    public function addQualification(): void
    {
        Gate::authorize($this->editingId === null ? 'courses.create' : 'courses.update');
        $this->qualifications[] = '';
    }

    public function removeQualification(int $index): void
    {
        Gate::authorize($this->editingId === null ? 'courses.create' : 'courses.update');
        if (array_key_exists($index, $this->qualifications)) {
            unset($this->qualifications[$index], $this->qualificationsSi[$index]);
            $this->qualificationsSi = array_values($this->qualificationsSi);
            $this->qualifications = array_values($this->qualifications);
        }
        if ($this->qualifications === []) {
            $this->qualifications = [''];
        }
    }

    public function save(): void
    {
        Gate::authorize($this->editingId === null ? 'courses.create' : 'courses.update');
        if ($this->isActive || ($this->editingId !== null && Course::query()->whereKey($this->editingId)->where('is_active', true)->exists())) {
            Gate::authorize('courses.publish');
        }
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'nameSi' => ['required', 'string', 'max:255'],
            'courseCategoryId' => ['nullable', 'integer', 'exists:course_categories,id'],
            'startDate' => ['nullable', 'date_format:Y-m-d'],
            'endDate' => ['nullable', 'date_format:Y-m-d'],
            'isActive' => ['boolean'],
            'qualifications' => ['array', 'max:30'],
            'qualificationsSi' => ['array', 'max:30'],
            'qualificationsSi.*' => ['nullable', 'string', 'max:500'],
            'qualifications.*' => ['nullable', 'string', 'max:500'],
        ]);

        $startDate = $validated['startDate'] ?? '';
        $endDate = $validated['endDate'] ?? '';
        if ($startDate !== '' && $endDate !== '' && $endDate < $startDate) {
            throw ValidationException::withMessages([
                'endDate' => 'The end date must be on or after the start date.',
            ]);
        }

        foreach ($validated['qualificationsSi'] as $index => $value) {
            if (trim((string) $value) !== '' && trim((string) ($validated['qualifications'][$index] ?? '')) === '') {
                throw ValidationException::withMessages(['qualifications.'.$index => 'Add the matching English qualification.']);
            }
        }
        $qualifications = array_filter(array_map(static fn ($value): string => trim((string) $value), $validated['qualifications']), static fn (string $value): bool => $value !== '');

        DB::transaction(function () use ($validated, $startDate, $endDate, $qualifications): void {
            $course = $this->editingId === null
                ? new Course
                : Course::query()->findOrFail($this->editingId);

            if (! $course->exists) {
                $course->sort_order = max(0, (int) Course::query()->max('sort_order')) + 1;
            }

            $course->fill([
                'name' => trim($validated['name']),
                'name_si' => trim($validated['nameSi']),
                'course_category_id' => $validated['courseCategoryId'] !== '' ? $validated['courseCategoryId'] : null,
                'start_date' => $startDate !== '' ? $startDate : null,
                'end_date' => $endDate !== '' ? $endDate : null,
                'is_active' => $validated['isActive'],
            ])->save();

            $course->qualifications()->delete();
            foreach ($qualifications as $index => $qualification) {
                $course->qualifications()->create([
                    'qualification' => $qualification,
                    'sort_order' => $index,
                    'qualification_si' => trim((string) ($validated['qualificationsSi'][$index] ?? '')) ?: null,
                ]);
            }
        });

        session()->flash('status', $this->editingId === null ? 'Course added.' : 'Course updated.');
        $this->resetForm();
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('courses.publish');
        $course = Course::query()->findOrFail($id);
        $course->update(['is_active' => ! $course->is_active]);
        session()->flash('status', $course->is_active ? 'Course activated.' : 'Course deactivated.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('courses.delete');
        Course::query()->findOrFail($id)->delete();
        session()->flash('status', 'Course deleted.');
        $this->resetPage();
    }

    public function cancel(): void
    {
        Gate::authorize('courses.view');
        $this->resetForm();
    }

    public function render(): View
    {
        Gate::authorize('courses.view');

        $courses = Course::query()
            ->with(['qualifications', 'category'])
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $search = trim($this->search);
                $query->where(function (Builder $courseQuery) use ($search): void {
                    $courseQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('name_si', 'like', '%'.$search.'%');
                });
            })
            ->when($this->selectedCategory !== null, function (Builder $query): void {
                $query->where('course_category_id', $this->selectedCategory);
            })
            ->when(in_array($this->statusFilter, ['active', 'inactive'], true), function (Builder $query): void {
                $query->where('is_active', $this->statusFilter === 'active');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        $categories = CourseCategory::query()
            ->withCount('courses')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.courses.course-index', [
            'courses' => $courses,
            'categories' => $categories,
            'courseCounts' => [
                'all' => Course::query()->count(),
                'active' => Course::query()->where('is_active', true)->count(),
                'inactive' => Course::query()->where('is_active', false)->count(),
            ],
        ])->layout('components.layouts.admin', ['title' => 'Courses']);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'formOpen', 'name', 'nameSi', 'courseCategoryId', 'startDate', 'endDate', 'isActive', 'qualifications']);
        $this->isActive = Gate::allows('courses.publish');
        $this->qualificationsSi = [];
        $this->qualifications = [''];
        $this->resetValidation();
    }
}
