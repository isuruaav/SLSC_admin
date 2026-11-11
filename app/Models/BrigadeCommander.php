<?php

namespace App\Models;

use App\Services\MediaUrlService;
use Database\Factories\BrigadeCommanderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $order_number
 * @property string $name_en
 * @property string|null $name_si
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string|null $period_en
 * @property string|null $period_si
 * @property string|null $secondary_tenure_en
 * @property string|null $secondary_tenure_si
 * @property string|null $image_path
 * @property int|null $image_media_id
 * @property int $sort_order
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MediaAsset|null $imageMedia
 * @property-read User|null $creator
 * @property-read User|null $updater
 */
final class BrigadeCommander extends Model
{
    /** @use HasFactory<BrigadeCommanderFactory> */
    use HasFactory;

    protected $table = 'brigade_commanders';

    protected $fillable = [
        'order_number',
        'name_en',
        'name_si',
        'start_date',
        'end_date',
        'period_en',
        'period_si',
        'secondary_tenure_en',
        'secondary_tenure_si',
        'image_path',
        'image_media_id',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'image_media_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function imageMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_media_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @param  Builder<BrigadeCommander>  $query
     * @return Builder<BrigadeCommander>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<BrigadeCommander>  $query
     * @return Builder<BrigadeCommander>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    public function nameForLocale(?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();

        if ($loc === 'si' && ! empty($this->name_si)) {
            return $this->name_si;
        }

        return $this->name_en;
    }

    public function periodForLocale(?string $locale = null): ?string
    {
        $loc = $locale ?: app()->getLocale();

        if ($loc === 'si' && ! empty($this->period_si)) {
            return $this->period_si;
        }

        if (! empty($this->period_en)) {
            return $this->period_en;
        }

        if ($this->start_date && $this->end_date) {
            $from = $this->start_date->format('Y - m - d');
            $to = $this->end_date->format('Y - m - d');

            return $loc === 'si' ? "{$from} සිට {$to} දක්වා" : "{$from} to {$to}";
        }

        if ($this->start_date) {
            $from = $this->start_date->format('Y - m - d');

            return $loc === 'si' ? "{$from} සිට" : "From {$from}";
        }

        return null;
    }

    public function secondaryTenureForLocale(?string $locale = null): ?string
    {
        $loc = $locale ?: app()->getLocale();

        if ($loc === 'si' && ! empty($this->secondary_tenure_si)) {
            return $this->secondary_tenure_si;
        }

        return $this->secondary_tenure_en;
    }

    public function displayOrderNumber(): string
    {
        if (! empty($this->order_number)) {
            return $this->order_number;
        }

        return sprintf('%02d', $this->sort_order ?: $this->id);
    }

    public function imageUrl(): ?string
    {
        if ($this->image_media_id && $this->imageMedia) {
            return app(MediaUrlService::class)->mediumOrOriginal($this->imageMedia);
        }

        if (! empty($this->image_path)) {
            if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
                return $this->image_path;
            }

            return asset($this->image_path);
        }

        return null;
    }
}
