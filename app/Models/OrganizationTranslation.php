<?php

namespace App\Models;

use Database\Factories\OrganizationTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $locale
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $summary
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class OrganizationTranslation extends Model
{
    /** @use HasFactory<OrganizationTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'locale',
        'title',
        'subtitle',
        'summary',
        'seo_title',
        'seo_description',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}
