<?php

namespace App\Models;

use App\Models\Concerns\HasVisibilitySchedule;
use App\Services\MediaUrlService;
use App\Services\SiteUrlService;
use Database\Factories\SpecialProgramFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SpecialProgram extends Model
{
    /** @use HasFactory<SpecialProgramFactory> */
    use HasFactory;

    use HasVisibilitySchedule;

    public const CATEGORY_HERITAGE = 'heritage';

    public const CATEGORY_COMMUNITY = 'community';

    public const CATEGORY_RESOURCES = 'resources';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title_en',
        'title_si',
        'url',
        'category',
        'image_media_id',
        'sort_order',
        'is_active', 'starts_at', 'ends_at',
        'open_in_new_tab',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'image_media_id' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime',
        'open_in_new_tab' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_media_id');
    }

    /**
     * Get image public URL if available.
     */
    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return app(MediaUrlService::class)->mediumOrOriginal($this->image);
    }

    public function linkUrl(): string
    {
        $urls = app(SiteUrlService::class);
        $path = $urls->normalizeLink($this->url);

        return $path === null ? '#' : $urls->resolve($path);
    }

    /**
     * Scope only active programs.
     *
     * @param  Builder<SpecialProgram>  $query
     * @return Builder<SpecialProgram>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->visibleNow();
    }

    /**
     * Scope ordered programs.
     *
     * @param  Builder<SpecialProgram>  $query
     * @return Builder<SpecialProgram>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Scope by category.
     *
     * @param  Builder<SpecialProgram>  $query
     * @return Builder<SpecialProgram>
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        if ($category === 'all' || trim($category) === '') {
            return $query;
        }

        return $query->where('category', $category);
    }

    /**
     * Get title for specified locale.
     */
    public function titleForLocale(?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();

        if ($loc === 'si' && ! empty($this->title_si)) {
            return $this->title_si;
        }

        return $this->title_en;
    }

    /**
     * Get category human-readable label.
     */
    public function categoryLabel(?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();

        if ($loc === 'si') {
            return match ($this->category) {
                self::CATEGORY_HERITAGE => 'උරුමය හා සම්ප්‍රදාය',
                self::CATEGORY_COMMUNITY => 'ප්‍රජාව හා සේවා',
                self::CATEGORY_RESOURCES => 'සම්පත් හා ව්‍යාපෘති',
                default => ucfirst($this->category),
            };
        }

        return match ($this->category) {
            self::CATEGORY_HERITAGE => 'Heritage',
            self::CATEGORY_COMMUNITY => 'Community',
            self::CATEGORY_RESOURCES => 'Resources',
            default => ucfirst($this->category),
        };
    }

    /**
     * Category options dictionary for selects.
     *
     * @return array<string, array{en: string, si: string}>
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_HERITAGE => [
                'en' => 'Heritage',
                'si' => 'උරුමය හා සම්ප්‍රදාය',
            ],
            self::CATEGORY_COMMUNITY => [
                'en' => 'Community',
                'si' => 'ප්‍රජාව හා සේවා',
            ],
            self::CATEGORY_RESOURCES => [
                'en' => 'Resources',
                'si' => 'සම්පත් හා ව්‍යාපෘති',
            ],
        ];
    }
}
