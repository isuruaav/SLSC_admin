<?php

namespace App\Models;

use App\Services\MediaUrlService;
use Database\Factories\FirstAppointmentHolderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $appointment_en
 * @property string|null $appointment_si
 * @property string $name_en
 * @property string|null $name_si
 * @property string|null $period_en
 * @property string|null $period_si
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
final class FirstAppointmentHolder extends Model
{
    /** @use HasFactory<FirstAppointmentHolderFactory> */
    use HasFactory;

    protected $table = 'first_appointment_holders';

    protected $fillable = [
        'appointment_en',
        'appointment_si',
        'name_en',
        'name_si',
        'period_en', 'start_date', 'end_date',
        'period_si',
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
            'image_media_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean', 'start_date' => 'date', 'end_date' => 'date',
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
     * @param  Builder<FirstAppointmentHolder>  $query
     * @return Builder<FirstAppointmentHolder>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<FirstAppointmentHolder>  $query
     * @return Builder<FirstAppointmentHolder>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    public function appointmentForLocale(?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();

        if ($loc === 'si' && ! empty($this->appointment_si)) {
            return $this->appointment_si;
        }

        return $this->appointment_en;
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
        if ($this->getAttribute('start_date')) {
            $from = $this->getAttribute('start_date')->format('Y - m - d');
            $to = $this->getAttribute('end_date')?->format('Y - m - d') ?? (($locale ?? app()->getLocale()) === 'si' ? 'අද දක්වා' : 'Present');

            return ($locale ?? app()->getLocale()) === 'si' ? "$from සිට $to දක්වා" : "$from to $to";
        }
        $loc = $locale ?: app()->getLocale();

        if ($loc === 'si' && ! empty($this->period_si)) {
            return $this->period_si;
        }

        return $this->period_en;
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
