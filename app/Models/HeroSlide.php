<?php

namespace App\Models;

use App\Enums\PageLocale;
use App\Models\Concerns\HasVisibilitySchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $title
 * @property string|null $subtitle
 * @property int|null $image_media_id
 * @property string|null $button_label
 * @property string|null $button_url
 * @property bool $is_active
 * @property int $sort_order
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property-read MediaAsset|null $image
 * @property-read Collection<int, HeroSlideTranslation> $translations
 */
final class HeroSlide extends Model
{
    use HasVisibilitySchedule;

    /** @var list<string> */
    protected $fillable = [
        'title',
        'subtitle',
        'image_media_id',
        'button_label',
        'button_url',
        'is_active', 'starts_at', 'ends_at',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        self::creating(static function (HeroSlide $slide): void {
            $uuid = $slide->getAttribute('uuid');

            if (! is_string($uuid) || trim($uuid) === '') {
                $slide->setAttribute(
                    'uuid',
                    (string) Str::uuid(),
                );
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'image_media_id' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime',
            'sort_order' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function image(): BelongsTo
    {
        return $this->belongsTo(
            MediaAsset::class,
            'image_media_id',
        );
    }

    /** @return HasMany<HeroSlideTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(
            HeroSlideTranslation::class,
        );
    }

    public function translation(
        PageLocale|string $locale,
        bool $fallbackToEnglish = true,
    ): ?HeroSlideTranslation {
        $localeCode = $locale instanceof PageLocale
            ? $locale->value
            : $locale;

        $translation = $this->translations->first(
            static fn (HeroSlideTranslation $item): bool => $item->locale->value === $localeCode,
        );

        if (
            $translation instanceof HeroSlideTranslation
            || ! $fallbackToEnglish
            || $localeCode === PageLocale::English->value
        ) {
            return $translation;
        }

        $englishTranslation = $this->translations->first(
            static fn (HeroSlideTranslation $item): bool => $item->locale === PageLocale::English,
        );

        return $englishTranslation instanceof HeroSlideTranslation
            ? $englishTranslation
            : null;
    }

    public function resolvedButtonUrl(PageLocale|string $locale = PageLocale::English): ?string
    {
        $value = $this->translation($locale)->button_url ?? $this->button_url;
        $value = trim($value ?? '');

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            $root = rtrim(url('/'), '/');
            $basePath = parse_url($root, PHP_URL_PATH);

            // Accept pasted paths that already include the installation directory.
            if (is_string($basePath) && $basePath !== '' && (
                $value === $basePath
                || str_starts_with($value, $basePath.'/')
                || str_starts_with($value, $basePath.'?')
                || str_starts_with($value, $basePath.'#')
            )) {
                $value = substr($value, strlen($basePath));
            }

            // Older slide links used /page/; the CMS page routes use /pages/.
            $value = preg_replace('~^/page/~', '/pages/', $value) ?? $value;
            $value = preg_replace('~^/(en|si|ta)/page/~', '/$1/pages/', $value) ?? $value;

            return $root.'/'.ltrim($value, '/');
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        return is_string($scheme)
            && in_array(strtolower($scheme), ['http', 'https'], true)
            && filter_var($value, FILTER_VALIDATE_URL) !== false
                ? $value
                : null;
    }

    /**
     * @param  Builder<HeroSlide>  $query
     * @return Builder<HeroSlide>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)->visibleNow()
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
