<?php

namespace App\Models;

use App\Enums\OrganizationBlockType;
use Database\Factories\OrganizationContentBlockFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $locale
 * @property string $block_type
 * @property string|null $title
 * @property string|null $content
 * @property array<string, mixed>|null $structured_data
 * @property int|null $media_id
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class OrganizationContentBlock extends Model
{
    /** @use HasFactory<OrganizationContentBlockFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'locale',
        'block_type',
        'title',
        'content',
        'structured_data',
        'media_id',
        'sort_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'structured_data' => 'array',
        'sort_order' => 'integer',
        'media_id' => 'integer',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }

    public function blockTypeEnum(): ?OrganizationBlockType
    {
        return OrganizationBlockType::tryFrom($this->block_type);
    }

    /**
     * @param  Builder<OrganizationContentBlock>  $query
     * @return Builder<OrganizationContentBlock>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }
}
