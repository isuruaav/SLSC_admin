<?php

namespace App\Livewire\Admin\Organizations;

use App\Enums\OrganizationBlockType;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationCategory;
use App\Models\OrganizationContentBlock;
use App\Models\OrganizationRevision;
use App\Models\OrganizationTranslation;
use App\Models\User;
use App\Services\OrganizationRevisionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

final class OrganizationEditor extends Component
{
    public ?int $organizationId = null;

    // Basic metadata
    public ?int $categoryId = null;

    public ?int $parentId = null;

    public string $slug = '';

    public string $shortName = '';

    public string $status = 'draft';

    public int $sortOrder = 0;

    public bool $isFeatured = false;

    public ?string $featuredImageUrl = null;

    public ?string $telephone = null;

    public ?string $email = null;

    public ?string $fax = null;

    public ?string $location = null;

    public ?string $website = null;

    public ?string $sourceUrl = null;

    // Multilingual Translations: en, si
    public string $activeLocale = 'en';

    /** @var array<string, array<string, string>> */
    public array $translations = [
        'en' => ['title' => '', 'subtitle' => '', 'summary' => '', 'seo_title' => '', 'seo_description' => ''],
        'si' => ['title' => '', 'subtitle' => '', 'summary' => '', 'seo_title' => '', 'seo_description' => ''],
    ];

    // Content Blocks
    /** @var array<int, array<string, mixed>> */
    public array $blocks = [];

    // Live preview toggle
    public bool $showPreviewModal = false;

    public function mount(?int $id = null): void
    {
        $this->organizationId = $id;

        if ($this->organizationId) {
            Gate::authorize('organizations.update');
            $this->loadOrganization();
        } else {
            Gate::authorize('organizations.create');
            $this->sortOrder = ((int) Organization::max('sort_order')) + 10;
            // Pre-seed an introductory paragraph block
            $this->addBlock('paragraph');
        }
    }

    private function loadOrganization(): void
    {
        /** @var Organization $org */
        $org = Organization::with(['translations', 'contentBlocks' => fn ($q) => $q->ordered()])->findOrFail($this->organizationId);

        $this->categoryId = $org->category_id;
        $this->parentId = $org->parent_id;
        $this->slug = $org->slug;
        $this->shortName = $org->short_name ?? '';
        $this->status = $org->status->value;
        $this->sortOrder = $org->sort_order;
        $this->isFeatured = $org->is_featured;
        $this->featuredImageUrl = $org->featured_image_url;
        $this->telephone = $org->telephone;
        $this->email = $org->email;
        $this->fax = $org->fax;
        $this->location = $org->location;
        $this->website = $org->website;
        $this->sourceUrl = $org->source_url;

        foreach ($org->translations as $t) {
            $loc = $t->locale;
            if (isset($this->translations[$loc])) {
                $this->translations[$loc] = [
                    'title' => $t->title ?? '',
                    'subtitle' => $t->subtitle ?? '',
                    'summary' => $t->summary ?? '',
                    'seo_title' => $t->seo_title ?? '',
                    'seo_description' => $t->seo_description ?? '',
                ];
            }
        }

        $this->blocks = [];
        foreach ($org->contentBlocks as $cb) {
            $this->blocks[] = [
                'id' => $cb->id,
                'temp_id' => Str::random(8),
                'locale' => $cb->locale,
                'block_type' => $cb->block_type,
                'title' => $cb->title ?? '',
                'content' => $cb->content ?? '',
                'structured_data' => $cb->structured_data ?? [],
                'sort_order' => $cb->sort_order,
            ];
        }
    }

    public function updatedTranslationsEnTitle(string $value): void
    {
        if (! $this->organizationId && empty($this->slug)) {
            $this->slug = Str::slug($value);
        }
    }

    public function switchLocale(string $locale): void
    {
        if (in_array($locale, ['en', 'si'], true)) {
            $this->activeLocale = $locale;
        }
    }

    // --- Block Manipulation ---

    public function addBlock(string $type): void
    {
        $defaultData = [];
        $defaultTitle = '';
        $defaultContent = '';

        if ($type === 'table') {
            $defaultTitle = 'Command Table';
            $defaultData = [
                'headers' => ['No', 'Rank & Name', 'Tenure From', 'Tenure To'],
                'rows' => [
                    ['01', 'Col / Lt Col Name', 'YYYY-MM-DD', 'YYYY-MM-DD'],
                ],
            ];
        } elseif ($type === 'heading') {
            $defaultTitle = 'Section Heading';
            $defaultData = ['level' => 'h2'];
        } elseif ($type === 'two_column') {
            $defaultData = [
                'col1' => 'Left column content goes here.',
                'col2' => 'Right column content goes here.',
            ];
        } elseif ($type === 'contact') {
            $defaultTitle = 'Official Contacts';
            $defaultData = [
                'telephone' => $this->telephone ?? '',
                'email' => $this->email ?? '',
                'location' => $this->location ?? '',
            ];
        } elseif ($type === 'timeline') {
            $defaultTitle = 'Historical Milestones';
            $defaultData = [
                ['year' => '1943', 'title' => 'Formation', 'description' => 'Ceylon Signals Corps was raised.'],
            ];
        } elseif ($type === 'list') {
            $defaultData = [
                'type' => 'unordered',
                'items' => ['Key responsibility 1', 'Key responsibility 2'],
            ];
        } elseif ($type === 'gallery') {
            $defaultData = [
                'images' => [
                    ['url' => '', 'caption' => ''],
                ],
            ];
        }

        $this->blocks[] = [
            'id' => null,
            'temp_id' => Str::random(8),
            'locale' => 'en',
            'block_type' => $type,
            'title' => $defaultTitle,
            'content' => $defaultContent,
            'structured_data' => $defaultData,
            'sort_order' => count($this->blocks) + 1,
        ];
    }

    public function removeBlock(int $index): void
    {
        if (isset($this->blocks[$index])) {
            array_splice($this->blocks, $index, 1);
            $this->resequenceBlockOrders();
        }
    }

    public function moveBlockUp(int $index): void
    {
        if ($index > 0 && isset($this->blocks[$index])) {
            $tmp = $this->blocks[$index - 1];
            $this->blocks[$index - 1] = $this->blocks[$index];
            $this->blocks[$index] = $tmp;
            $this->resequenceBlockOrders();
        }
    }

    public function moveBlockDown(int $index): void
    {
        if ($index < count($this->blocks) - 1 && isset($this->blocks[$index])) {
            $tmp = $this->blocks[$index + 1];
            $this->blocks[$index + 1] = $this->blocks[$index];
            $this->blocks[$index] = $tmp;
            $this->resequenceBlockOrders();
        }
    }

    public function duplicateBlock(int $index): void
    {
        if (isset($this->blocks[$index])) {
            $clone = $this->blocks[$index];
            $clone['id'] = null;
            $clone['temp_id'] = Str::random(8);
            array_splice($this->blocks, $index + 1, 0, [$clone]);
            $this->resequenceBlockOrders();
        }
    }

    private function resequenceBlockOrders(): void
    {
        foreach (array_keys($this->blocks) as $idx) {
            $this->blocks[$idx]['sort_order'] = $idx + 1;
        }
    }

    // --- Dynamic Table Editing ---

    public function addTableRow(int $blockIndex): void
    {
        if (! isset($this->blocks[$blockIndex])) {
            return;
        }

        $headers = $this->blocks[$blockIndex]['structured_data']['headers'] ?? ['Col 1', 'Col 2'];
        $colsCount = count($headers);
        $newRow = array_fill(0, $colsCount, '');

        $this->blocks[$blockIndex]['structured_data']['rows'][] = $newRow;
    }

    public function removeTableRow(int $blockIndex, int $rowIndex): void
    {
        if (isset($this->blocks[$blockIndex]['structured_data']['rows'][$rowIndex])) {
            array_splice($this->blocks[$blockIndex]['structured_data']['rows'], $rowIndex, 1);
        }
    }

    public function addTableColumn(int $blockIndex): void
    {
        if (! isset($this->blocks[$blockIndex])) {
            return;
        }

        $currentCols = count($this->blocks[$blockIndex]['structured_data']['headers'] ?? []);
        $newColNum = $currentCols + 1;
        $this->blocks[$blockIndex]['structured_data']['headers'][] = "Column {$newColNum}";

        if (isset($this->blocks[$blockIndex]['structured_data']['rows'])) {
            foreach ($this->blocks[$blockIndex]['structured_data']['rows'] as &$row) {
                $row[] = '';
            }
        }
    }

    public function removeTableColumn(int $blockIndex, int $colIndex): void
    {
        if (! isset($this->blocks[$blockIndex])) {
            return;
        }

        if (count($this->blocks[$blockIndex]['structured_data']['headers'] ?? []) <= 1) {
            return; // Maintain at least 1 column
        }

        array_splice($this->blocks[$blockIndex]['structured_data']['headers'], $colIndex, 1);

        if (isset($this->blocks[$blockIndex]['structured_data']['rows'])) {
            foreach ($this->blocks[$blockIndex]['structured_data']['rows'] as &$row) {
                if (isset($row[$colIndex])) {
                    array_splice($row, $colIndex, 1);
                }
            }
        }
    }

    // --- Dynamic Gallery Editing ---
    public function addGalleryImage(int $blockIndex): void
    {
        if (isset($this->blocks[$blockIndex])) {
            $this->blocks[$blockIndex]['structured_data']['images'][] = ['url' => '', 'caption' => ''];
        }
    }

    public function removeGalleryImage(int $blockIndex, int $imgIndex): void
    {
        if (isset($this->blocks[$blockIndex]['structured_data']['images'][$imgIndex])) {
            array_splice($this->blocks[$blockIndex]['structured_data']['images'], $imgIndex, 1);
        }
    }

    // --- Dynamic Timeline Editing ---
    public function addTimelineItem(int $blockIndex): void
    {
        if (isset($this->blocks[$blockIndex])) {
            $this->blocks[$blockIndex]['structured_data'][] = [
                'year' => date('Y'),
                'title' => 'Event Title',
                'description' => 'Event description',
            ];
        }
    }

    public function removeTimelineItem(int $blockIndex, int $itemIndex): void
    {
        if (isset($this->blocks[$blockIndex]['structured_data'][$itemIndex])) {
            array_splice($this->blocks[$blockIndex]['structured_data'], $itemIndex, 1);
        }
    }

    // --- Dynamic List Editing ---
    public function addListItem(int $blockIndex): void
    {
        if (isset($this->blocks[$blockIndex])) {
            $this->blocks[$blockIndex]['structured_data']['items'][] = '';
        }
    }

    public function removeListItem(int $blockIndex, int $itemIndex): void
    {
        if (isset($this->blocks[$blockIndex]['structured_data']['items'][$itemIndex])) {
            array_splice($this->blocks[$blockIndex]['structured_data']['items'], $itemIndex, 1);
        }
    }

    // --- Saving & Publishing ---

    public function save(bool $publish = false): void
    {
        $uniqueRule = $this->organizationId
            ? 'unique:organizations,slug,'.$this->organizationId
            : 'unique:organizations,slug';

        $this->validate([
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', $uniqueRule],
            'shortName' => ['nullable', 'string', 'max:50'],
            'categoryId' => ['nullable', 'exists:organization_categories,id'],
            'parentId' => ['nullable', 'exists:organizations,id'],
            'translations.en.title' => ['required', 'string', 'max:255'],
            'translations.en.summary' => ['nullable', 'string'],
            'translations.si.title' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'email', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'featuredImageUrl' => ['nullable', 'string', 'max:2048'],
        ]);

        Gate::authorize($this->organizationId ? 'organizations.update' : 'organizations.create');
        if ($this->organizationId && Organization::query()->whereKey($this->organizationId)->where('status', 'published')->exists()) {
            Gate::authorize('organizations.publish');
        }
        $this->status = $publish ? 'published' : 'draft';
        if ($publish) {
            Gate::authorize('organizations.publish');
            $this->status = OrganizationStatus::Published->value;
        } else {
            if ($this->organizationId) {
                Gate::authorize('organizations.update');
            } else {
                Gate::authorize('organizations.create');
            }
        }

        // Auto-generate source URL based on slug
        $autoSourceUrl = ! empty($this->sourceUrl)
            ? $this->sourceUrl
            : 'https://alt.army.lk/signaller/organization/'.$this->slug;

        DB::transaction(function () use ($publish, $autoSourceUrl) {
            $userId = $this->actor()->id;

            $orgData = [
                'category_id' => $this->categoryId,
                'parent_id' => $this->parentId,
                'slug' => $this->slug,
                'short_name' => $this->shortName ?: null,
                'status' => OrganizationStatus::from($this->status),
                'sort_order' => $this->sortOrder,
                'is_featured' => $this->isFeatured,
                'featured_image_url' => $this->featuredImageUrl,
                'telephone' => $this->telephone,
                'email' => $this->email,
                'fax' => $this->fax,
                'location' => $this->location,
                'website' => $this->website,
                'source_url' => $autoSourceUrl,
                'updated_by' => $userId,
            ];

            if ($publish && ! $this->organizationId) {
                $orgData['published_at'] = now();
            } elseif ($publish && $this->organizationId) {
                $orgData['published_at'] = now();
            }

            if ($this->organizationId) {
                /** @var Organization $org */
                $org = Organization::findOrFail($this->organizationId);
                app(OrganizationRevisionService::class)->capture($org, $userId, 'Before content edit');
                $org->update($orgData);
            } else {
                $orgData['created_by'] = $userId;
                $org = Organization::create($orgData);
                $this->organizationId = $org->id;
            }

            // Save Translations
            foreach ($this->translations as $locale => $tData) {
                if (! empty($tData['title'])) {
                    OrganizationTranslation::updateOrCreate(
                        ['organization_id' => $org->id, 'locale' => $locale],
                        [
                            'title' => $tData['title'],
                            'subtitle' => $tData['subtitle'] ?? null,
                            'summary' => $tData['summary'] ?? null,
                            'seo_title' => $tData['seo_title'] ?? null,
                            'seo_description' => $tData['seo_description'] ?? null,
                        ]
                    );
                }
            }

            // Synchronize Content Blocks
            OrganizationContentBlock::where('organization_id', $org->id)->delete();

            $order = 1;
            foreach ($this->blocks as $b) {
                OrganizationContentBlock::create([
                    'organization_id' => $org->id,
                    'locale' => $b['locale'] ?? 'en',
                    'block_type' => $b['block_type'],
                    'title' => $b['title'] ?: null,
                    'content' => $b['content'] ?: null,
                    'structured_data' => $b['structured_data'] ?? null,
                    'sort_order' => $order++,
                ]);
            }
        });

        session()->flash('status', 'Organization ['.$this->translations['en']['title'].'] saved successfully.');
        $this->redirect(route('admin.organizations.index'), navigate: true);
    }

    public function togglePreviewModal(): void
    {
        $this->showPreviewModal = ! $this->showPreviewModal;
    }

    public function render(): View
    {
        $categories = OrganizationCategory::ordered()->get();
        $potentialParents = Organization::where('id', '!=', $this->organizationId ?? 0)->get();

        return view('livewire.admin.organizations.organization-editor', [
            'categories' => $categories,
            'potentialParents' => $potentialParents,
            'revisions' => OrganizationRevision::query()->where('organization_id', $this->organizationId)->latest('id')->limit(10)->get(),
            'blockTypeCases' => OrganizationBlockType::cases(),
        ])->layout('components.layouts.admin', [
            'title' => $this->organizationId ? 'Edit Organization' : 'Add New Organization',
        ]);
    }

    public function restoreRevision(int $id): void
    {
        $revision = OrganizationRevision::query()->where('organization_id', $this->organizationId)->findOrFail($id);
        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);
        app(OrganizationRevisionService::class)->restore($revision->id, $actor);
        $this->redirectRoute('admin.organizations.edit', ['id' => $this->organizationId], navigate: true);
    }

    private function actor(): User
    {
        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
