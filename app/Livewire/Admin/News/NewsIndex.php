<?php

namespace App\Livewire\Admin\News;

use App\Enums\NewsLocale;
use App\Enums\NewsStatus;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\User;
use App\Services\NewsDeletionService;
use App\Services\NewsWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class NewsIndex extends Component
{
    use WithPagination;

    /** @var list<string> */
    private const SORTABLE_FIELDS = [
        'title',
        'locale',
        'status',
        'updated_at',
        'published_at',
        'created_at',
    ];

    /** @var list<int> */
    private const PER_PAGE_OPTIONS = [10, 15, 25, 50];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(except: 'all')]
    public string $category = 'all';

    #[Url(as: 'type', except: 'all')]
    public string $newsType = 'all';

    #[Url(except: 'all')]
    public string $locale = 'all';

    #[Url(as: 'records', except: 'active')]
    public string $recordState = 'active';

    #[Url(as: 'sort', except: 'updated_at')]
    public string $sortField = 'updated_at';

    #[Url(as: 'direction', except: 'desc')]
    public string $sortDirection = 'desc';

    public int $perPage = 15;

    public function mount(): void
    {
        Gate::authorize('news.view');
        $this->normaliseQueryParameters();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->normaliseStatus();
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->normaliseCategory();
        $this->resetPage();
    }

    public function updatedNewsType(): void
    {
        $this->normaliseNewsType();
        $this->resetPage();
    }

    public function updatedLocale(): void
    {
        $this->normaliseLocale();
        $this->resetPage();
    }

    public function updatedRecordState(): void
    {
        $this->normaliseRecordState();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->normalisePerPage();
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = 'all';
        $this->category = 'all';
        $this->newsType = 'all';
        $this->locale = 'all';
        $this->recordState = 'active';
        $this->sortField = 'updated_at';
        $this->sortDirection = 'desc';
        $this->perPage = 15;
        $this->resetPage();
    }

    public function sort(string $field): void
    {
        if (! in_array($field, self::SORTABLE_FIELDS, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc'
                ? 'desc'
                : 'asc';
            $this->resetPage();

            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function publishArticle(int $newsId): void
    {
        $news = News::query()->findOrFail($newsId);

        app(NewsWorkflowService::class)->publishImmediately(
            $news,
            $this->actor(),
        );

        session()->flash(
            'status',
            "{$news->title} was published.",
        );
    }

    public function unpublishArticle(int $newsId): void
    {
        Gate::authorize('news.view');

        $news = News::query()->findOrFail($newsId);

        $unpublished = app(NewsWorkflowService::class)->unpublishForEditing(
            $news,
            $this->actor(),
        );

        session()->flash(
            'status',
            $unpublished > 1
                ? "{$unpublished} language versions were unpublished and returned to Draft."
                : "{$news->title} was unpublished and returned to Draft.",
        );
    }

    public function deleteArticle(int $newsId): void
    {
        $news = News::query()->findOrFail($newsId);
        $title = $news->title;

        app(NewsDeletionService::class)->delete(
            $news,
            $this->actor(),
        );

        session()->flash(
            'status',
            "{$title} was moved to Trash.",
        );
    }

    public function restoreArticle(int $newsId): void
    {
        $news = News::withTrashed()->findOrFail($newsId);
        $title = $news->title;

        app(NewsDeletionService::class)->restore(
            $newsId,
            $this->actor(),
        );

        session()->flash(
            'status',
            "{$title} was restored successfully.",
        );
    }

    public function forceDeleteArticle(int $newsId): void
    {
        Gate::authorize('news.delete');

        $news = News::withTrashed()->findOrFail($newsId);
        $title = $news->title;

        app(NewsDeletionService::class)->forceDelete($newsId, $this->actor());

        $this->resetPage();

        session()->flash('status', "{$title} was permanently deleted.");
    }

    public function render(): View
    {
        Gate::authorize('news.view');
        $this->normaliseQueryParameters();

        $query = News::query();

        if ($this->recordState === 'trashed') {
            $query->onlyTrashed();
        }

        $query->with([
            'category',
            'creator:id,name',
            'updater:id,name',
            'translationVersions',
        ]);

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(
                function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%");
                },
            );
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->locale !== 'all') {
            $query->where('locale', $this->locale);
        }

        if ($this->newsType === 'photo_story') {
            $query->photoStories();
        } elseif ($this->newsType === 'news_feature') {
            $query->newsFeatures();
        }

        if ($this->category !== 'all') {
            $query->where('category_id', (int) $this->category);
        }

        $news = $query
            ->orderBy(
                $this->normalisedSortField(),
                $this->normalisedSortDirection(),
            )
            ->paginate($this->perPage);

        return view(
            'livewire.admin.news.news-index',
            [
                'news' => $news,
                'categories' => NewsCategory::query()
                    ->active()
                    ->ordered()
                    ->get(),
                'statuses' => [
                    NewsStatus::Draft,
                    NewsStatus::Published,
                ],
                'locales' => app(\App\Services\SiteLanguageService::class)->choices(NewsLocale::cases()),
                'canManageCategories' => Gate::allows('news.categories.manage'),
            ],
        )->layout(
            'components.layouts.admin',
            [
                'title' => 'News',
            ],
        );
    }

    private function normaliseQueryParameters(): void
    {
        $this->sortField = $this->normalisedSortField();
        $this->sortDirection = $this->normalisedSortDirection();
        $this->normaliseStatus();
        $this->normaliseCategory();
        $this->normaliseNewsType();
        $this->normaliseLocale();
        $this->normaliseRecordState();
        $this->normalisePerPage();
    }

    private function normaliseNewsType(): void
    {
        if (! in_array($this->newsType, ['all', 'photo_story', 'news_feature'], true)) {
            $this->newsType = 'all';
        }
    }

    private function normaliseStatus(): void
    {
        if ($this->status === 'all') {
            return;
        }

        $values = array_map(
            static fn (NewsStatus $status): string => $status->value,
            NewsStatus::cases(),
        );

        if (! in_array($this->status, $values, true)) {
            $this->status = 'all';
        }
    }

    private function normaliseCategory(): void
    {
        if ($this->category === 'all') {
            return;
        }

        if (! ctype_digit($this->category)) {
            $this->category = 'all';
        }
    }

    private function normaliseLocale(): void
    {
        if ($this->locale === 'all') {
            return;
        }

        if (! in_array($this->locale, NewsLocale::values(), true)) {
            $this->locale = 'all';
        }
    }

    private function normaliseRecordState(): void
    {
        if (! in_array($this->recordState, ['active', 'trashed'], true)) {
            $this->recordState = 'active';
        }
    }

    private function normalisePerPage(): void
    {
        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 15;
        }
    }

    private function normalisedSortField(): string
    {
        return in_array($this->sortField, self::SORTABLE_FIELDS, true)
            ? $this->sortField
            : 'updated_at';
    }

    /** @return 'asc'|'desc' */
    private function normalisedSortDirection(): string
    {
        return $this->sortDirection === 'asc'
            ? 'asc'
            : 'desc';
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
}
