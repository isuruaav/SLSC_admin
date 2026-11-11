<?php

namespace App\Livewire\Admin\Documents;

use App\Enums\DocumentStatus;
use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Models\Document;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\DocumentUploadManager;
use App\Services\MediaUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

final class DocumentUploads extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $savedDocumentId = null;

    public bool $showEditor = false;

    #[Locked]
    public int $formVersion = 0;

    public string $title = '';

    public string $titleSi = '';

    public string $search = '';

    public string $statusFilter = 'all';

    public int $perPage = 10;

    public string $sortField = 'updated_at';

    public string $sortDirection = 'desc';

    public mixed $englishFile = null;

    public mixed $sinhalaFile = null;

    public function mount(?Document $document = null): void
    {
        Gate::authorize('documents.view');

        if ($document !== null && $document->exists) {
            $this->edit((int) $document->getKey());
        } elseif (request()->routeIs('admin.documents.create')) {
            $this->create();
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        if (! in_array(
            $this->statusFilter,
            [
                'all',
                'published',
                'not_public', 'trashed',
            ],
            true,
        )) {
            $this->statusFilter = 'all';
        }

        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array(
            $this->perPage,
            [
                10,
                25,
                50,
            ],
            true,
        )) {
            $this->perPage = 10;
        }

        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array(
            $field,
            [
                'title',
                'status',
                'updated_at',
            ],
            true,
        )) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc'
                ? 'desc'
                : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $field === 'updated_at'
                ? 'desc'
                : 'asc';
        }

        $this->resetPage();
    }

    public function clearTableFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';

        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('documents.view');
        Gate::authorize('documents.create');

        $this->clearForm();
        $this->showEditor = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('documents.view');
        Gate::authorize('documents.update');

        $document = Document::query()
            ->whereKey($id)
            ->firstOrFail();

        $this->editingId = (int) $document->getKey();
        $this->title = $document->titleForLocale('en');

        $titleSi = $document->getAttribute('title_si');

        $this->titleSi = is_string($titleSi)
            ? $titleSi
            : '';

        $this->englishFile = null;
        $this->sinhalaFile = null;

        $this->resetValidation();
        $this->formVersion++;
        $this->showEditor = true;
    }

    public function cancel(): void
    {
        Gate::authorize('documents.view');

        $this->clearForm();
    }

    public function save(): void
    {
        $this->persist(false);
    }

    public function saveAndPublish(): void
    {
        Gate::authorize('documents.publish');
        $this->persist(true);
    }

    private function persist(bool $publish): void
    {
        Gate::authorize('documents.view');

        Gate::authorize(
            $this->editingId === null
                ? 'documents.create'
                : 'documents.update',
        );

        $this->title = trim($this->title);
        $this->titleSi = trim($this->titleSi);

        $this->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'titleSi' => [
                'nullable',
                'string',
                'max:255',
            ],
            'englishFile' => [
                'nullable',
                'file',
                'extensions:pdf,doc,docx,xls,xlsx',
                'max:10240',
            ],
            'sinhalaFile' => [
                'nullable',
                'file',
                'extensions:pdf,doc,docx,xls,xlsx',
                'max:10240',
            ],
        ]);

        if (
            $this->editingId === null
            && ! $this->englishFile instanceof TemporaryUploadedFile
            && ! $this->sinhalaFile instanceof TemporaryUploadedFile
        ) {
            throw ValidationException::withMessages([
                'englishFile' => 'Upload at least one document file.',
            ]);
        }

        $actor = $this->actor();

        $english = $this->upload(
            $this->englishFile,
            'englishFile',
            $actor,
        );

        $sinhala = $this->upload(
            $this->sinhalaFile,
            'sinhalaFile',
            $actor,
        );

        $document = app(DocumentUploadManager::class)->save(
            actor: $actor,
            documentId: $this->editingId,
            title: $this->title,
            titleSi: $this->titleSi,
            englishFile: $english,
            sinhalaFile: $sinhala,
            publish: $publish,
        );

        $this->savedDocumentId = (int) $document->getKey();

        $this->clearForm();
        $this->resetPage();

        session()->flash(
            'status',
            $publish ? 'Document saved and published successfully.' : 'Document saved as a private draft.',
        );
    }

    public function delete(int $id): void
    {
        Gate::authorize('documents.view');

        app(DocumentUploadManager::class)->delete(
            $id,
            $this->actor(),
        );

        if ($this->editingId === $id) {
            $this->clearForm();
        }

        $this->resetPage();

        session()->flash(
            'status',
            'Document moved to Trash. You can restore it as a draft.',
        );
    }

    public function render(): View
    {
        Gate::authorize('documents.view');

        $search = mb_substr(
            trim($this->search),
            0,
            255,
        );

        $query = Document::query()
            ->when($this->statusFilter === 'trashed', fn (Builder $query) => $query->onlyTrashed())
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $query->where(
                        function (Builder $titles) use ($search): void {
                            $titles
                                ->where(
                                    'title',
                                    'like',
                                    '%'.$search.'%',
                                )
                                ->orWhere(
                                    'title_si',
                                    'like',
                                    '%'.$search.'%',
                                );
                        },
                    );
                },
            );

        if ($this->statusFilter === 'published') {
            $query->where(
                'status',
                DocumentStatus::Published->value,
            );
        } elseif ($this->statusFilter === 'not_public') {
            $query->where(
                'status',
                '!=',
                DocumentStatus::Published->value,
            );
        }

        $sortDirection = $this->sortDirection === 'asc'
            ? 'asc'
            : 'desc';

        match ($this->sortField) {
            'title' => $query->orderBy(
                'title',
                $sortDirection,
            ),
            'status' => $query->orderBy(
                'status',
                $sortDirection,
            ),
            default => $query->orderBy(
                'updated_at',
                $sortDirection,
            ),
        };

        if ($this->sortField !== 'updated_at') {
            $query->orderByDesc('updated_at');
        }

        $query->orderByDesc('id');

        if (! in_array(
            $this->perPage,
            [
                10,
                25,
                50,
            ],
            true,
        )) {
            $this->perPage = 10;
        }

        /** @var view-string $viewName */
        $viewName = 'livewire.admin.documents.document-uploads';

        return view(
            $viewName,
            [
                'documents' => $query->paginate(
                    $this->perPage,
                ),
                'versions' => $this->editingId !== null ? Document::query()->findOrFail($this->editingId)->versions()->with('media')->latest('version')->get() : collect(),
                'savedDocument' => $this->savedDocumentId !== null
                    ? Document::query()->published()->find($this->savedDocumentId)
                    : null,
            ],
        )->layout(
            'components.layouts.admin',
            [
                'title' => 'Document Uploads',
            ],
        );
    }

    private function upload(
        mixed $file,
        string $field,
        User $actor,
    ): ?MediaAsset {
        if (! $file instanceof TemporaryUploadedFile) {
            return null;
        }

        Gate::authorize('media.upload');

        try {
            return app(MediaUploadService::class)->upload(
                file: $file,
                privateStorage: true,
                type: MediaType::Document,
                visibility: MediaVisibility::Public,
                actor: $actor,
                title: $field === 'sinhalaFile'
                    && $this->titleSi !== ''
                        ? $this->titleSi
                        : $this->title,
            );
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                $field => collect(
                    $exception->errors(),
                )
                    ->flatten()
                    ->implode(' '),
            ]);
        }
    }

    private function clearForm(): void
    {
        $this->reset([
            'showEditor',
            'editingId',
            'title',
            'titleSi',
            'englishFile',
            'sinhalaFile',
        ]);

        $this->formVersion++;
        $this->resetValidation();
    }

    private function actor(): User
    {
        $actor = Auth::user();

        abort_unless(
            $actor instanceof User,
            403,
        );

        return $actor;
    }

    public function restore(int $id): void
    {
        Gate::authorize('documents.view');
        app(DocumentUploadManager::class)->restore($id, $this->actor());
        session()->flash('status', 'Document restored as a draft.');
        $this->resetPage();
    }

    public function publish(int $id): void
    {
        $this->setPublished($id, true);
    }

    public function unpublish(int $id): void
    {
        $this->setPublished($id, false);
    }

    private function setPublished(int $id, bool $publish): void
    {
        Gate::authorize('documents.view');
        Gate::authorize('documents.publish');
        $record = Document::query()->whereKey($id)->firstOrFail();
        app(DocumentUploadManager::class)->save($this->actor(), $id, (string) $record->title, (string) $record->title_si, null, null, $publish);
        session()->flash('status', $publish ? 'Document published.' : 'Document returned to draft.');
    }

    public function restoreVersion(int $id): void
    {
        Gate::authorize('documents.view');
        abort_unless($this->editingId !== null, 404);
        app(DocumentUploadManager::class)->restoreUploadVersion(Document::query()->findOrFail($this->editingId), $id, $this->actor());
        session()->flash('status', 'Document version restored.');
    }
}
