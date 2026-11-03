<?php

namespace App\Livewire\Admin\Media;

use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\MediaAsset;
use App\Models\News;
use App\Models\NewsImage;
use App\Models\User;
use App\Services\MediaDeletionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

final class MediaIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public string $visibilityFilter = '';

    public string $view = 'active';

    public string $libraryMode = 'all';

    public string $usageType = 'all';

    public string $selectedUsageType = '';

    public int $selectedUsageId = 0;

    public function mount(): void
    {
        Gate::authorize('media.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedVisibilityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedUsageType(): void
    {
        if (! in_array(
            $this->usageType,
            [
                'all',
                'news',
                'gallery',
            ],
            true,
        )) {
            $this->usageType = 'all';
        }

        $this->selectedUsageType = '';
        $this->selectedUsageId = 0;

        $this->resetPage();
    }

    public function updatedView(): void
    {
        if (! in_array(
            $this->view,
            [
                'active',
                'trash',
            ],
            true,
        )) {
            $this->view = 'active';
        }

        $this->resetPage();
    }

    public function setLibraryMode(string $mode): void
    {
        if (! in_array(
            $mode,
            [
                'all',
                'usage',
                'other',
            ],
            true,
        )) {
            return;
        }

        $this->libraryMode = $mode;
        $this->selectedUsageType = '';
        $this->selectedUsageId = 0;

        $this->resetPage();
    }

    public function setUsageType(string $type): void
    {
        if (! in_array(
            $type,
            [
                'all',
                'news',
                'gallery',
            ],
            true,
        )) {
            return;
        }

        $this->usageType = $type;
        $this->selectedUsageType = '';
        $this->selectedUsageId = 0;

        $this->resetPage();
    }

    public function openUsageGroup(
        string $type,
        int $id,
    ): void {
        if (
            ! in_array(
                $type,
                [
                    'news',
                    'gallery',
                ],
                true,
            )
            || $id <= 0
        ) {
            return;
        }

        $this->libraryMode = 'usage';
        $this->selectedUsageType = $type;
        $this->selectedUsageId = $id;

        $this->resetPage();
    }

    public function closeUsageGroup(): void
    {
        $this->selectedUsageType = '';
        $this->selectedUsageId = 0;

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->typeFilter = '';
        $this->visibilityFilter = '';

        $this->resetPage();
    }

    public function deleteMedia(int $mediaId): void
    {
        Gate::authorize('media.delete');

        $media = MediaAsset::query()
            ->findOrFail($mediaId);

        app(MediaDeletionService::class)->delete(
            media: $media,
            actor: $this->actor(),
        );

        session()->flash(
            'status',
            'Media asset was moved to trash.',
        );

        $this->resetPage();
    }

    public function restoreMedia(int $mediaId): void
    {
        Gate::authorize('media.delete');

        $media = MediaAsset::onlyTrashed()
            ->findOrFail($mediaId);

        app(MediaDeletionService::class)->restore(
            media: $media,
            actor: $this->actor(),
        );

        session()->flash(
            'status',
            'Media asset was restored successfully.',
        );

        $this->resetPage();
    }

    public function forceDeleteMedia(int $mediaId): void
    {
        Gate::authorize('media.delete');

        $media = MediaAsset::onlyTrashed()
            ->findOrFail($mediaId);

        app(MediaDeletionService::class)->forceDelete(
            media: $media,
            actor: $this->actor(),
        );

        session()->flash(
            'status',
            'Media asset was permanently deleted.',
        );

        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('media.view');

        $media = null;
        $usageGroups = [];
        $selectedUsageGroup = null;

        if (
            $this->libraryMode === 'usage'
            && $this->selectedUsageType !== ''
            && $this->selectedUsageId > 0
        ) {
            $selectedUsageGroup = $this->selectedUsageGroup();

            $query = $this->selectedUsageMediaQuery();

            $media = $query
                ->with(['uploader', 'variants'])
                ->latest('id')
                ->paginate(20);
        } elseif ($this->libraryMode === 'usage') {
            $usageGroups = $this->usageGroups();
        } else {
            $query = $this->filteredMediaQuery();

            if ($this->libraryMode === 'other') {
                $this->applyOtherMediaFilter($query);
            }

            $media = $query
                ->with(['uploader', 'variants'])
                ->latest('id')
                ->paginate(20);
        }

        /** @var view-string $viewName */
        $viewName = 'livewire.admin.media.media-index';

        return view(
            $viewName,
            [
                'media' => $media,
                'usageGroups' => $usageGroups,
                'selectedUsageGroup' => $selectedUsageGroup,

                'mediaTypes' => MediaType::cases(),

                'visibilities' => MediaVisibility::cases(),

                'activeCount' => MediaAsset::query()
                    ->count(),

                'trashCount' => MediaAsset::onlyTrashed()
                    ->count(),
            ],
        )->layout(
            'components.layouts.admin',
            [
                'title' => 'Media Library',
            ],
        );
    }

    /**
     * @return Builder<MediaAsset>
     */
    private function filteredMediaQuery(
        bool $applySearch = true,
    ): Builder {
        $query = MediaAsset::query();

        if ($this->view === 'trash') {
            $query->onlyTrashed();
        }

        if ($applySearch) {
            $this->applySearch($query);
        }

        $this->applyTypeFilter($query);
        $this->applyVisibilityFilter($query);

        return $query;
    }

    /**
     * @param  Builder<MediaAsset>  $query
     */
    private function applySearch(Builder $query): void
    {
        $search = trim(
            $this->search,
        );

        if ($search === '') {
            return;
        }

        $query->where(
            function (
                Builder $searchQuery,
            ) use ($search): void {
                $like = '%'.$search.'%';

                $searchQuery
                    ->where(
                        'title',
                        'like',
                        $like,
                    )
                    ->orWhere(
                        'original_name',
                        'like',
                        $like,
                    )
                    ->orWhere(
                        'alt_text',
                        'like',
                        $like,
                    )
                    ->orWhere(
                        'caption',
                        'like',
                        $like,
                    );
            },
        );
    }

    /**
     * @param  Builder<MediaAsset>  $query
     */
    private function applyTypeFilter(Builder $query): void
    {
        if ($this->typeFilter === '') {
            return;
        }

        $type = MediaType::tryFrom(
            $this->typeFilter,
        );

        if (! $type instanceof MediaType) {
            return;
        }

        $query->where(
            'type',
            $type->value,
        );
    }

    /**
     * @param  Builder<MediaAsset>  $query
     */
    private function applyVisibilityFilter(Builder $query): void
    {
        if ($this->visibilityFilter === '') {
            return;
        }

        $visibility = MediaVisibility::tryFrom(
            $this->visibilityFilter,
        );

        if (! $visibility instanceof MediaVisibility) {
            return;
        }

        $query->where(
            'visibility',
            $visibility->value,
        );
    }

    /**
     * "Other Media" means media that is not directly linked
     * to News or Gallery records. It may still be used by
     * other modules such as Pages, Hero Slider, Leadership,
     * Site Settings or Documents.
     *
     * @param  Builder<MediaAsset>  $query
     */
    private function applyOtherMediaFilter(
        Builder $query,
    ): void {
        $query
            ->whereNotIn(
                'id',
                NewsImage::query()
                    ->select('media_asset_id'),
            )
            ->whereNotIn(
                'id',
                GalleryImage::query()
                    ->select('media_asset_id'),
            )
            ->whereNotIn(
                'id',
                News::query()
                    ->whereNotNull('featured_image_id')
                    ->select('featured_image_id'),
            )
            ->whereNotIn(
                'id',
                Gallery::query()
                    ->whereNotNull('cover_media_id')
                    ->select('cover_media_id'),
            );
    }

    /**
     * @return list<int>
     */
    private function matchingUsageMediaIds(): array
    {
        $ids = [];

        foreach (
            $this->filteredMediaQuery(
                applySearch: false,
            )->pluck('id') as $id
        ) {
            if (! is_numeric($id)) {
                continue;
            }

            $mediaId = (int) $id;

            if ($mediaId <= 0) {
                continue;
            }

            $ids[] = $mediaId;
        }

        return $ids;
    }

    /**
     * @return list<array{
     *     type: 'news'|'gallery',
     *     id: int,
     *     title: string,
     *     subtitle: string,
     *     media_count: int
     * }>
     */
    private function usageGroups(): array
    {
        $allowedMediaIds = $this->matchingUsageMediaIds();

        if ($allowedMediaIds === []) {
            return [];
        }

        /** @var array<int, true> $allowedLookup */
        $allowedLookup = [];

        foreach ($allowedMediaIds as $mediaId) {
            $allowedLookup[$mediaId] = true;
        }

        $groups = [];

        if (
            $this->usageType === 'all'
            || $this->usageType === 'news'
        ) {
            foreach (
                $this->newsUsageGroups(
                    $allowedLookup,
                ) as $group
            ) {
                $groups[] = $group;
            }
        }

        if (
            $this->usageType === 'all'
            || $this->usageType === 'gallery'
        ) {
            foreach (
                $this->galleryUsageGroups(
                    $allowedLookup,
                ) as $group
            ) {
                $groups[] = $group;
            }
        }

        $search = mb_strtolower(
            trim($this->search),
        );

        if ($search !== '') {
            $groups = array_values(
                array_filter(
                    $groups,
                    static function (array $group) use ($search): bool {
                        return str_contains(
                            mb_strtolower(
                                $group['title'].' '.$group['subtitle'],
                            ),
                            $search,
                        );
                    },
                ),
            );
        }

        usort(
            $groups,
            static function (array $left, array $right): int {
                if ($left['type'] !== $right['type']) {
                    return $left['type'] === 'news'
                        ? -1
                        : 1;
                }

                return strcasecmp(
                    $left['title'],
                    $right['title'],
                );
            },
        );

        return $groups;
    }

    /**
     * @param  array<int, true>  $allowedLookup
     * @return list<array{
     *     type: 'news',
     *     id: int,
     *     title: string,
     *     subtitle: string,
     *     media_count: int
     * }>
     */
    private function newsUsageGroups(
        array $allowedLookup,
    ): array {
        $newsRecords = News::query()
            ->orderBy('id')
            ->get([
                'id',
                'title',
                'locale',
                'translation_group',
                'featured_image_id',
            ]);

        if ($newsRecords->isEmpty()) {
            return [];
        }

        $newsIds = $newsRecords
            ->pluck('id')
            ->map(
                static fn (int|string $id): int => (int) $id,
            )
            ->values()
            ->all();

        $imageRows = NewsImage::query()
            ->whereIn(
                'news_id',
                $newsIds,
            )
            ->get([
                'news_id',
                'media_asset_id',
            ]);

        /**
         * @var array<int, array<int, true>>
         */
        $mediaByNewsId = [];

        foreach ($imageRows as $imageRow) {
            $newsId = (int) $imageRow->getAttribute(
                'news_id',
            );

            $mediaId = (int) $imageRow->getAttribute(
                'media_asset_id',
            );

            if (
                $newsId <= 0
                || $mediaId <= 0
                || ! isset(
                    $allowedLookup[$mediaId],
                )
            ) {
                continue;
            }

            $mediaByNewsId[$newsId][$mediaId] = true;
        }

        /**
         * @var array<string, array{
         *     canonical_id: int,
         *     title: string,
         *     locales: array<string, true>,
         *     media: array<int, true>,
         *     has_english: bool
         * }>
         */
        $grouped = [];

        foreach ($newsRecords as $news) {
            $newsId = (int) $news->getKey();

            if ($newsId <= 0) {
                continue;
            }

            $title = trim(
                (string) $news->getAttribute(
                    'title',
                ),
            );

            if ($title === '') {
                $title = 'Untitled News #'.$newsId;
            }

            $rawLocale = $news->getRawOriginal(
                'locale',
            );

            $locale = is_string($rawLocale)
                && trim($rawLocale) !== ''
                    ? strtolower(
                        trim($rawLocale),
                    )
                    : 'en';

            $translationGroup = $news->getAttribute(
                'translation_group',
            );

            $groupKey = is_string($translationGroup)
                && trim($translationGroup) !== ''
                    ? 'translation:'.trim(
                        $translationGroup,
                    )
                    : 'news:'.$newsId;

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'canonical_id' => $newsId,
                    'title' => $title,
                    'locales' => [],
                    'media' => [],
                    'has_english' => false,
                ];
            }

            $grouped[$groupKey]['locales'][$locale] = true;

            if (
                $locale === 'en'
                && ! $grouped[$groupKey]['has_english']
            ) {
                $grouped[$groupKey]['canonical_id'] = $newsId;
                $grouped[$groupKey]['title'] = $title;
                $grouped[$groupKey]['has_english'] = true;
            }

            foreach (
                $mediaByNewsId[$newsId] ?? [] as $mediaId => $enabled
            ) {
                $grouped[$groupKey]['media'][$mediaId] = $enabled;
            }

            $featuredImageId = $news->getAttribute(
                'featured_image_id',
            );

            if (
                is_numeric($featuredImageId)
                && isset(
                    $allowedLookup[(int) $featuredImageId],
                )
            ) {
                $grouped[$groupKey]['media'][
                    (int) $featuredImageId
                ] = true;
            }
        }

        $groups = [];

        foreach ($grouped as $group) {
            $mediaCount = count(
                $group['media'],
            );

            if ($mediaCount === 0) {
                continue;
            }

            $locales = array_keys(
                $group['locales'],
            );

            sort($locales);

            $groups[] = [
                'type' => 'news',
                'id' => $group['canonical_id'],
                'title' => $group['title'],
                'subtitle' => 'News'
                    .($locales !== []
                        ? ' • '.strtoupper(
                            implode(' / ', $locales),
                        )
                        : ''),
                'media_count' => $mediaCount,
            ];
        }

        return $groups;
    }

    /**
     * @param  array<int, true>  $allowedLookup
     * @return list<array{
     *     type: 'gallery',
     *     id: int,
     *     title: string,
     *     subtitle: string,
     *     media_count: int
     * }>
     */
    private function galleryUsageGroups(
        array $allowedLookup,
    ): array {
        $galleries = Gallery::query()
            ->orderBy('id')
            ->get([
                'id',
                'title',
                'cover_media_id',
            ]);

        if ($galleries->isEmpty()) {
            return [];
        }

        $galleryIds = $galleries
            ->pluck('id')
            ->map(
                static fn (int|string $id): int => (int) $id,
            )
            ->values()
            ->all();

        $imageRows = GalleryImage::query()
            ->whereIn(
                'gallery_id',
                $galleryIds,
            )
            ->get([
                'gallery_id',
                'media_asset_id',
            ]);

        /**
         * @var array<int, array<int, true>>
         */
        $mediaByGalleryId = [];

        foreach ($imageRows as $imageRow) {
            $galleryId = (int) $imageRow->getAttribute(
                'gallery_id',
            );

            $mediaId = (int) $imageRow->getAttribute(
                'media_asset_id',
            );

            if (
                $galleryId <= 0
                || $mediaId <= 0
                || ! isset(
                    $allowedLookup[$mediaId],
                )
            ) {
                continue;
            }

            $mediaByGalleryId[$galleryId][$mediaId] = true;
        }

        $groups = [];

        foreach ($galleries as $gallery) {
            $galleryId = (int) $gallery->getKey();

            if ($galleryId <= 0) {
                continue;
            }

            $media = $mediaByGalleryId[$galleryId] ?? [];

            $coverMediaId = $gallery->getAttribute(
                'cover_media_id',
            );

            if (
                is_numeric($coverMediaId)
                && isset(
                    $allowedLookup[(int) $coverMediaId],
                )
            ) {
                $media[(int) $coverMediaId] = true;
            }

            $mediaCount = count($media);

            if ($mediaCount === 0) {
                continue;
            }

            $title = trim(
                (string) $gallery->getAttribute(
                    'title',
                ),
            );

            if ($title === '') {
                $title = 'Untitled Gallery #'.$galleryId;
            }

            $groups[] = [
                'type' => 'gallery',
                'id' => $galleryId,
                'title' => $title,
                'subtitle' => 'Gallery',
                'media_count' => $mediaCount,
            ];
        }

        return $groups;
    }

    /**
     * @return array{
     *     type: 'news'|'gallery',
     *     id: int,
     *     title: string,
     *     subtitle: string
     * }|null
     */
    private function selectedUsageGroup(): ?array
    {
        if (
            $this->selectedUsageType === 'news'
            && $this->selectedUsageId > 0
        ) {
            $news = News::query()->find(
                $this->selectedUsageId,
            );

            if (! $news instanceof News) {
                return null;
            }

            $translationGroup = $news->getAttribute(
                'translation_group',
            );

            $versions = is_string($translationGroup)
                && trim($translationGroup) !== ''
                    ? News::query()
                        ->where(
                            'translation_group',
                            trim($translationGroup),
                        )
                        ->orderBy('id')
                        ->get([
                            'id',
                            'title',
                            'locale',
                        ])
                    : collect([$news]);

            $canonical = $versions->first(
                static function (News $version): bool {
                    return $version->getRawOriginal(
                        'locale',
                    ) === 'en';
                },
            );

            if (! $canonical instanceof News) {
                $canonical = $versions->first();
            }

            if (! $canonical instanceof News) {
                $canonical = $news;
            }

            $locales = [];

            foreach ($versions as $version) {
                $rawLocale = $version->getRawOriginal(
                    'locale',
                );

                if (
                    is_string($rawLocale)
                    && trim($rawLocale) !== ''
                ) {
                    $locales[] = strtoupper(
                        trim($rawLocale),
                    );
                }
            }

            $locales = array_values(
                array_unique($locales),
            );

            $title = trim(
                (string) $canonical->getAttribute(
                    'title',
                ),
            );

            if ($title === '') {
                $title = 'Untitled News #'
                    .$this->selectedUsageId;
            }

            return [
                'type' => 'news',
                'id' => (int) $canonical->getKey(),
                'title' => $title,
                'subtitle' => 'News'
                    .($locales !== []
                        ? ' • '.implode(
                            ' / ',
                            $locales,
                        )
                        : ''),
            ];
        }

        if (
            $this->selectedUsageType === 'gallery'
            && $this->selectedUsageId > 0
        ) {
            $gallery = Gallery::query()->find(
                $this->selectedUsageId,
            );

            if (! $gallery instanceof Gallery) {
                return null;
            }

            $title = trim(
                (string) $gallery->getAttribute(
                    'title',
                ),
            );

            if ($title === '') {
                $title = 'Untitled Gallery #'
                    .$this->selectedUsageId;
            }

            return [
                'type' => 'gallery',
                'id' => (int) $gallery->getKey(),
                'title' => $title,
                'subtitle' => 'Gallery',
            ];
        }

        return null;
    }

    /**
     * @return Builder<MediaAsset>
     */
    private function selectedUsageMediaQuery(): Builder
    {
        $query = $this->filteredMediaQuery();

        if (
            $this->selectedUsageType === 'news'
            && $this->selectedUsageId > 0
        ) {
            $news = News::query()->find(
                $this->selectedUsageId,
            );

            if (! $news instanceof News) {
                return $query->whereRaw('1 = 0');
            }

            $translationGroup = $news->getAttribute(
                'translation_group',
            );

            $newsIds = is_string($translationGroup)
                && trim($translationGroup) !== ''
                    ? News::query()
                        ->where(
                            'translation_group',
                            trim($translationGroup),
                        )
                        ->pluck('id')
                        ->map(
                            static fn (int|string $id): int => (int) $id,
                        )
                        ->values()
                        ->all()
                    : [
                        (int) $news->getKey(),
                    ];

            $mediaIds = NewsImage::query()
                ->whereIn(
                    'news_id',
                    $newsIds,
                )
                ->pluck('media_asset_id')
                ->map(
                    static fn (int|string $id): int => (int) $id,
                )
                ->filter(
                    static fn (int $id): bool => $id > 0,
                )
                ->values()
                ->all();

            $featuredIds = News::query()
                ->whereIn(
                    'id',
                    $newsIds,
                )
                ->whereNotNull(
                    'featured_image_id',
                )
                ->pluck(
                    'featured_image_id',
                )
                ->map(
                    static fn (int|string $id): int => (int) $id,
                )
                ->filter(
                    static fn (int $id): bool => $id > 0,
                )
                ->values()
                ->all();

            $mediaIds = array_values(
                array_unique([
                    ...$mediaIds,
                    ...$featuredIds,
                ]),
            );

            return $query->whereIn(
                'id',
                $mediaIds,
            );
        }

        if (
            $this->selectedUsageType === 'gallery'
            && $this->selectedUsageId > 0
        ) {
            $gallery = Gallery::query()->find(
                $this->selectedUsageId,
            );

            if (! $gallery instanceof Gallery) {
                return $query->whereRaw('1 = 0');
            }

            $mediaIds = GalleryImage::query()
                ->where(
                    'gallery_id',
                    $this->selectedUsageId,
                )
                ->pluck(
                    'media_asset_id',
                )
                ->map(
                    static fn (int|string $id): int => (int) $id,
                )
                ->filter(
                    static fn (int $id): bool => $id > 0,
                )
                ->values()
                ->all();

            $coverMediaId = $gallery->getAttribute(
                'cover_media_id',
            );

            if (
                is_numeric($coverMediaId)
                && (int) $coverMediaId > 0
            ) {
                $mediaIds[] = (int) $coverMediaId;
            }

            return $query->whereIn(
                'id',
                array_values(
                    array_unique($mediaIds),
                ),
            );
        }

        return $query->whereRaw('1 = 0');
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
