<?php

namespace App\Livewire\Admin\Publications;

use App\Models\Publication;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PublicationService;
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

final class PublicationUploads extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public int $formVersion = 0;

    public bool $formOpen = false;

    public string $title = '';

    public string $titleSi = '';

    public string $locale = 'en';

    public string $languageFilter = 'all';

    public string $issueNumber = '';

    public string $issueYear = '';

    public bool $isPublished = false;

    public mixed $pdf = null;

    public mixed $cover = null;

    public string $search = '';

    public string $statusFilter = 'all';

    public bool $headerSettingsOpen = false;

    public string $pageTitleEn = '';

    public string $pageTitleSi = '';

    public string $pageKickerEn = '';

    public string $pageKickerSi = '';

    public string $pageSubtitleEn = '';

    public string $pageSubtitleSi = '';

    public function mount(): void
    {
        Gate::authorize('documents.view');
        $this->loadHeaderSettings();
        if (request()->filled('edit')) {
            $this->edit((int) request()->query('edit'));
        }
    }

    public function loadHeaderSettings(): void
    {
        $settings = SiteSetting::current();
        $this->pageTitleEn = (string) ($settings->publications_title_en ?? 'Signaller Magazine');
        $this->pageTitleSi = (string) ($settings->publications_title_si ?? 'සිග්නලර් සඟරාව');
        $this->pageKickerEn = (string) ($settings->publications_kicker_en ?? 'Explore our library');
        $this->pageKickerSi = (string) ($settings->publications_kicker_si ?? 'අපගේ සඟරා');
        $this->pageSubtitleEn = (string) ($settings->publications_subtitle_en ?? 'Explore our latest journals and publications. Select a cover to read the PDF.');
        $this->pageSubtitleSi = (string) ($settings->publications_subtitle_si ?? 'ශ්‍රී ලංකා සංඥා බලකායේ නවතම සඟරා. PDF ගොනුව කියවීමට කවරය මත ක්ලික් කරන්න.');
    }

    public function toggleHeaderSettings(): void
    {
        $this->headerSettingsOpen = ! $this->headerSettingsOpen;
        if ($this->headerSettingsOpen) {
            $this->loadHeaderSettings();
        }
    }

    public function saveHeaderSettings(): void
    {
        Gate::authorize('documents.update');

        $this->validate([
            'pageTitleEn' => ['required', 'string', 'max:255'],
            'pageTitleSi' => ['nullable', 'string', 'max:255'],
            'pageKickerEn' => ['nullable', 'string', 'max:255'],
            'pageKickerSi' => ['nullable', 'string', 'max:255'],
            'pageSubtitleEn' => ['nullable', 'string', 'max:1000'],
            'pageSubtitleSi' => ['nullable', 'string', 'max:1000'],
        ]);

        $settings = SiteSetting::current();
        $settings->update([
            'publications_title_en' => trim($this->pageTitleEn) !== '' ? trim($this->pageTitleEn) : 'Signaller Magazine',
            'publications_title_si' => trim($this->pageTitleSi) !== '' ? trim($this->pageTitleSi) : 'සිග්නලර් සඟරාව',
            'publications_kicker_en' => trim($this->pageKickerEn) !== '' ? trim($this->pageKickerEn) : 'Explore our library',
            'publications_kicker_si' => trim($this->pageKickerSi) !== '' ? trim($this->pageKickerSi) : 'අපගේ සඟරා',
            'publications_subtitle_en' => trim($this->pageSubtitleEn) !== '' ? trim($this->pageSubtitleEn) : null,
            'publications_subtitle_si' => trim($this->pageSubtitleSi) !== '' ? trim($this->pageSubtitleSi) : null,
        ]);

        session()->flash('publication-success', 'Page title and description updated successfully.');
        $this->headerSettingsOpen = false;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('documents.create');
        Gate::authorize('media.upload');
        $this->clearForm();
        $this->formOpen = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('documents.view');
        Gate::authorize('documents.update');
        $record = Publication::query()->findOrFail($id);
        if ($record->is_published) {
            Gate::authorize('documents.publish');
        }
        $this->clearForm();
        $this->editingId = $id;
        $this->title = $record->title;
        $this->titleSi = $record->title_si ?? '';
        $this->locale = $record->locale;
        $this->issueNumber = (string) $record->issue_number;
        $this->issueYear = (string) $record->issue_year;
        $this->isPublished = $record->is_published;
        $this->formOpen = true;
    }

    public function cancel(): void
    {
        Gate::authorize('documents.view');
        $this->clearForm();
    }

    public function save(): void
    {
        $this->persist($this->isPublished);
    }

    private function persist(bool $publish): void
    {
        $this->validate(['issueNumber' => ['nullable', 'string', 'max:80'], 'issueYear' => ['nullable', 'integer', 'between:1900,9999']]);
        foreach (['pdf', 'cover'] as $field) {
            if ($this->{$field} !== null && ! $this->{$field} instanceof TemporaryUploadedFile) {
                $message = $field === 'pdf'
                    ? 'Select the PDF again and wait for its upload to finish.'
                    : 'Select the cover image again and wait for its upload to finish.';
                throw ValidationException::withMessages([$field => $message]);
            }
        }
        app(PublicationService::class)->save(
            actor: $this->actor(), title: $this->title, titleSi: $this->titleSi,
            publish: $publish, pdf: $this->pdf, cover: $this->cover,
            publication: $this->editingId === null ? null : Publication::query()->findOrFail($this->editingId),
            locale: $this->locale,
            issueNumber: $this->issueNumber, issueYear: $this->issueYear !== '' ? (int) $this->issueYear : null,
        );
        session()->flash('publication-success', $publish ? 'Publication saved and published.' : 'Publication saved as a draft.');
        $this->clearForm();
        $this->resetPage();
    }

    public function saveAndPublish(): void
    {
        Gate::authorize('documents.publish');
        $this->persist(true);
    }

    public function publish(int $id): void
    {
        app(PublicationService::class)->setPublished(Publication::query()->findOrFail($id), $this->actor(), true);
        session()->flash('publication-success', 'Publication published.');
    }

    public function unpublish(int $id): void
    {
        app(PublicationService::class)->setPublished(Publication::query()->findOrFail($id), $this->actor(), false);
        session()->flash('publication-success', 'Publication returned to draft.');
    }

    public function delete(int $id): void
    {
        app(PublicationService::class)->delete(Publication::query()->findOrFail($id), $this->actor());
        if ($this->editingId === $id) {
            $this->clearForm();
        }
        session()->flash('publication-success', 'Publication moved to Trash. You can restore it as a draft.');
        $this->resetPage();
    }

    public function restore(int $id): void
    {
        app(PublicationService::class)->restore(Publication::onlyTrashed()->findOrFail($id), $this->actor());
        session()->flash('publication-success', 'Publication restored as a draft.');
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('documents.view');
        $publications = Publication::query()
            ->when(in_array($this->languageFilter, ['en', 'si'], true), fn (Builder $query) => $query->where('locale', $this->languageFilter))
            ->when($this->statusFilter === 'trashed', fn (Builder $query) => $query->onlyTrashed())
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('title', 'like', '%'.trim($this->search).'%')->orWhere('title_si', 'like', '%'.trim($this->search).'%');
                });
            })
            ->when(in_array($this->statusFilter, ['published', 'draft'], true), fn (Builder $query) => $query->where('is_published', $this->statusFilter === 'published'))
            ->latest('id')->paginate(10);

        return view('livewire.admin.publications.publication-uploads', [
            'publications' => $publications,
            'editing' => $this->editingId === null ? null : Publication::query()->find($this->editingId),
        ])->layout('components.layouts.admin', ['title' => 'Publications']);
    }

    private function clearForm(): void
    {
        $this->reset(['editingId', 'title', 'titleSi', 'pdf', 'cover', 'isPublished', 'formOpen', 'locale', 'issueNumber', 'issueYear']);
        $this->formVersion++;
        $this->resetValidation();
    }

    private function actor(): User
    {
        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);

        return $actor;
    }

    public function updatedLanguageFilter(): void
    {
        $this->resetPage();
    }
}
