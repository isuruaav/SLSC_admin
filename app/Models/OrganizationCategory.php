<?php

namespace App\Models;

use Database\Factories\OrganizationCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $name_en
 * @property string|null $name_si
 * @property string|null $name_ta
 * @property string|null $description_en
 * @property string|null $description_si
 * @property string|null $description_ta
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class OrganizationCategory extends Model
{
    /** @use HasFactory<OrganizationCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name_en',
        'name_si',
        'name_ta',
        'description_en',
        'description_si',
        'description_ta',
        'sort_order',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<Organization, $this>
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'category_id')->orderBy('sort_order', 'asc');
    }

    /**
     * @param  Builder<OrganizationCategory>  $query
     * @return Builder<OrganizationCategory>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<OrganizationCategory>  $query
     * @return Builder<OrganizationCategory>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    public function localizedName(?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();

        if ($loc === 'si' && ! empty($this->name_si)) {
            return $this->name_si;
        }

        if ($loc === 'ta' && ! empty($this->name_ta)) {
            return $this->name_ta;
        }

        return $this->name_en;
    }

    public function localizedDescription(?string $locale = null): ?string
    {
        $loc = $locale ?: app()->getLocale();

        if ($loc === 'si' && ! empty($this->description_si)) {
            return $this->description_si;
        }

        if ($loc === 'ta' && ! empty($this->description_ta)) {
            return $this->description_ta;
        }

        return $this->description_en;
    }
}
