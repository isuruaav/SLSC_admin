<?php

namespace App\Livewire\Admin\Organizations;

use App\Models\OrganizationCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

final class OrganizationCategoryIndex extends Component
{
    public bool $showModal = false;

    public ?int $editingCategoryId = null;

    public string $slug = '';

    public string $name_en = '';

    public string $name_si = '';

    public string $description_en = '';

    public string $description_si = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    /** @return array<string, list<string>> */
    protected function rules(): array
    {
        $uniqueRule = $this->editingCategoryId
            ? 'unique:organization_categories,slug,'.$this->editingCategoryId
            : 'unique:organization_categories,slug';

        return [
            'name_en' => ['required', 'string', 'max:255'],
            'name_si' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', $uniqueRule],
            'description_en' => ['nullable', 'string'],
            'description_si' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer'],
            'is_active' => ['boolean'],
        ];
    }

    public function updatedNameEn(string $value): void
    {
        if (! $this->editingCategoryId && empty($this->slug)) {
            $this->slug = Str::slug($value);
        }
    }

    public function startCreate(): void
    {
        Gate::authorize('organizations.categories.manage');

        $this->resetValidation();
        $this->editingCategoryId = null;
        $this->slug = '';
        $this->name_en = '';
        $this->name_si = '';
        $this->description_en = '';
        $this->description_si = '';
        $this->sort_order = (int) OrganizationCategory::max('sort_order') + 1;
        $this->is_active = true;
        $this->showModal = true;
    }

    public function startEdit(int $id): void
    {
        Gate::authorize('organizations.categories.manage');

        $this->resetValidation();
        $cat = OrganizationCategory::findOrFail($id);

        $this->editingCategoryId = $cat->id;
        $this->slug = $cat->slug;
        $this->name_en = $cat->name_en;
        $this->name_si = $cat->name_si ?? '';
        $this->description_en = $cat->description_en ?? '';
        $this->description_si = $cat->description_si ?? '';
        $this->sort_order = $cat->sort_order;
        $this->is_active = $cat->is_active;
        $this->showModal = true;
    }

    /** @param list<int> $orderedIds */
    public function reorderCategories(array $orderedIds): void
    {
        Gate::authorize('organizations.categories.manage');

        foreach ($orderedIds as $index => $id) {
            OrganizationCategory::where('id', (int) $id)->update([
                'sort_order' => $index + 1,
            ]);
        }

        session()->flash('status', 'Category order updated successfully.');
    }

    public function save(): void
    {
        Gate::authorize('organizations.categories.manage');

        $validated = $this->validate();

        if ($this->editingCategoryId) {
            $cat = OrganizationCategory::findOrFail($this->editingCategoryId);
            $cat->update($validated);
            session()->flash('status', "Category [{$cat->name_en}] updated successfully.");
        } else {
            $cat = OrganizationCategory::create($validated);
            session()->flash('status', "Category [{$cat->name_en}] created successfully.");
        }

        $this->showModal = false;
        $this->editingCategoryId = null;
    }

    public function deleteCategory(int $id): void
    {
        Gate::authorize('organizations.categories.manage');

        $cat = OrganizationCategory::withCount('organizations')->findOrFail($id);
        if ($cat->organizations_count > 0) {
            session()->flash('error', "Cannot delete category [{$cat->name_en}] because it contains {$cat->organizations_count} units.");

            return;
        }

        $name = $cat->name_en;
        $cat->delete();
        session()->flash('status', "Category [{$name}] deleted successfully.");
    }

    public function cancel(): void
    {
        $this->showModal = false;
        $this->editingCategoryId = null;
    }

    public function render(): View
    {
        Gate::authorize('organizations.categories.manage');

        $categories = OrganizationCategory::query()
            ->withCount('organizations')
            ->ordered()
            ->get();

        return view('livewire.admin.organizations.organization-category-index', [
            'categories' => $categories,
        ])->layout('components.layouts.admin', ['title' => 'Organization Categories']);
    }
}
