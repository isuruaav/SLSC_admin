<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use App\Services\MediaUrlService;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $category_id
 * @property int|null $parent_id
 * @property string $slug
 * @property string|null $short_name
 * @property int|null $featured_image_id
 * @property string|null $featured_image_url
 * @property OrganizationStatus $status
 * @property int $sort_order
 * @property bool $is_featured
 * @property string|null $email
 * @property string|null $telephone
 * @property string|null $fax
 * @property string|null $location
 * @property string|null $website
 * @property string|null $source_url
 * @property Carbon|null $imported_at
 * @property Carbon|null $published_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'parent_id',
        'slug',
        'short_name',
        'featured_image_id',
        'featured_image_url',
        'status',
        'sort_order',
        'is_featured',
        'email',
        'telephone',
        'fax',
        'location',
        'website',
        'source_url',
        'imported_at',
        'published_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => OrganizationStatus::class,
        'sort_order' => 'integer',
        'is_featured' => 'boolean',
        'category_id' => 'integer',
        'parent_id' => 'integer',
        'featured_image_id' => 'integer',
        'imported_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<OrganizationCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(OrganizationCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Organization, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order', 'asc');
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'featured_image_id');
    }

    /**
     * @return HasMany<OrganizationTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(OrganizationTranslation::class, 'organization_id');
    }

    /**
     * @return HasMany<OrganizationContentBlock, $this>
     */
    public function contentBlocks(): HasMany
    {
        return $this->hasMany(OrganizationContentBlock::class, 'organization_id')->orderBy('sort_order', 'asc');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', OrganizationStatus::Published)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /**
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function scopeCategory(Builder $query, ?int $categoryId = null): Builder
    {
        if ($categoryId === null || $categoryId <= 0) {
            return $query;
        }

        return $query->where('category_id', $categoryId);
    }

    /**
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function scopeSearch(Builder $query, ?string $term = null): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('slug', 'like', "%{$term}%")
                ->orWhere('short_name', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%")
                ->orWhereHas('translations', function (Builder $tq) use ($term): void {
                    $tq->where('title', 'like', "%{$term}%")
                        ->orWhere('subtitle', 'like', "%{$term}%")
                        ->orWhere('summary', 'like', "%{$term}%");
                });
        });
    }

    public function translationForLocale(?string $locale = null): ?OrganizationTranslation
    {
        $loc = $locale ?: app()->getLocale();

        /** @var OrganizationTranslation|null $translation */
        $translation = $this->translations->firstWhere('locale', $loc);

        if ($translation === null && $loc !== 'en') {
            $translation = $this->translations->firstWhere('locale', 'en');
        }

        return $translation;
    }

    public function titleForLocale(?string $locale = null): string
    {
        $tr = $this->translationForLocale($locale);

        return $tr?->title ?: ($this->short_name ?: $this->slug);
    }

    public function subtitleForLocale(?string $locale = null): ?string
    {
        return $this->translationForLocale($locale)?->subtitle;
    }

    public function summaryForLocale(?string $locale = null): ?string
    {
        return $this->translationForLocale($locale)?->summary;
    }

    public function seoTitleForLocale(?string $locale = null): string
    {
        $tr = $this->translationForLocale($locale);

        return $tr?->seo_title ?: $this->titleForLocale($locale);
    }

    public function seoDescriptionForLocale(?string $locale = null): ?string
    {
        $tr = $this->translationForLocale($locale);

        return $tr?->seo_description ?: $this->summaryForLocale($locale);
    }

    /**
     * @return Collection<int, OrganizationContentBlock>
     */
    public function blocksForLocale(string $locale = 'en'): Collection
    {
        $blocks = $this->contentBlocks->where('locale', $locale);

        if ($blocks->isEmpty() && $locale !== 'en') {
            $blocks = $this->contentBlocks->where('locale', 'en');
        }

        return $blocks->values();
    }

    public function featuredImageUrl(): ?string
    {
        if ($this->featuredImage) {
            return app(MediaUrlService::class)->mediumOrOriginal($this->featuredImage);
        }

        if (! empty($this->featured_image_url)) {
            return $this->featured_image_url;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function availableLocales(): array
    {
        return array_values($this->translations->map(static fn (OrganizationTranslation $translation): string => $translation->locale)->unique()->all());
    }
}
