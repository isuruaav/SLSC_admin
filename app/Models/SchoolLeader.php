<?php

namespace App\Models;

use App\Enums\SchoolLeadershipAppointment;
use App\Enums\SchoolLeadershipRank;
use App\Services\MediaUrlService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $position_id
 * @property string $role_key
 * @property string|null $rank
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string|null $secondary_tenure_en
 * @property string|null $secondary_tenure_si
 * @property string $title_en
 * @property string|null $title_si
 * @property string|null $name_en
 * @property string|null $name_si
 * @property int|null $image_media_id
 * @property string|null $image_path
 * @property bool $is_active
 * @property bool $profile_enabled
 * @property string|null $profile_html_en
 * @property string|null $profile_html_si
 * @property string|null $profile_css
 * @property string|null $profile_compiled_css
 * @property int $sort_order
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property-read SchoolLeadershipPosition|null $position
 * @property-read MediaAsset|null $image
 */
final class SchoolLeader extends Model
{
    public const ROLE_COMMANDANT = 'commandant';

    public const ROLE_CHIEF_INSTRUCTOR = 'chief_instructor';

    public const ROLE_ADJUTANT = 'adjutant';

    public const ROLE_WARRANT_OFFICER = 'warrant_officer';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'position_id',
        'role_key',
        'rank',
        'start_date',
        'end_date',
        'secondary_tenure_en',
        'secondary_tenure_si',
        'title_en',
        'title_si',
        'name_en',
        'name_si',
        'image_media_id',
        'image_path',
        'is_active',
        'profile_enabled',
        'profile_html_en',
        'profile_html_si',
        'profile_css',
        'profile_compiled_css',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position_id' => 'integer',
            'image_media_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'profile_enabled' => 'boolean',
            'sort_order' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SchoolLeadershipPosition, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(
            SchoolLeadershipPosition::class,
            'position_id',
        );
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(
            MediaAsset::class,
            'image_media_id',
        );
    }

    /**
     * @param  Builder<SchoolLeader>  $query
     * @return Builder<SchoolLeader>
     */
    public function scopeActive(
        Builder $query,
    ): Builder {
        return $query
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @param  Builder<SchoolLeader>  $query
     * @return Builder<SchoolLeader>
     */
    public function scopeCurrent(
        Builder $query,
    ): Builder {
        return $query->whereNull(
            'end_date',
        );
    }

    public function titleForLocale(
        string $locale,
    ): string {
        if (
            $this->position
            instanceof SchoolLeadershipPosition
        ) {
            return $this->position->nameForLocale(
                $locale,
            );
        }

        if (
            $locale === 'si'
            && is_string($this->title_si)
            && trim($this->title_si) !== ''
        ) {
            return $this->title_si;
        }

        return $this->title_en;
    }

    public function nameForLocale(
        string $locale,
    ): string {
        if (
            $locale === 'si'
            && is_string($this->name_si)
            && trim($this->name_si) !== ''
        ) {
            return $this->name_si;
        }

        return is_string($this->name_en)
            ? $this->name_en
            : '';
    }

    /**
     * Legacy enum helper for the original four appointments.
     *
     * New dynamic appointment types may return null here.
     */
    public function appointment(): ?SchoolLeadershipAppointment
    {
        return SchoolLeadershipAppointment::tryFrom(
            $this->role_key,
        );
    }

    public function rankOption(): ?SchoolLeadershipRank
    {
        return is_string($this->rank)
            ? SchoolLeadershipRank::tryFrom(
                $this->rank,
            )
            : null;
    }

    public function appointmentLabel(): string
    {
        if (
            $this->position
            instanceof SchoolLeadershipPosition
        ) {
            return $this->position->name_en;
        }

        return $this->appointment()?->label()
            ?? $this->title_en;
    }

    public function appointmentLabelForLocale(
        string $locale,
    ): string {
        if (
            $this->position
            instanceof SchoolLeadershipPosition
        ) {
            return $this->position->nameForLocale(
                $locale,
            );
        }

        $appointment = $this->appointment();

        if ($appointment !== null) {
            return $appointment->labelForLocale(
                $locale,
            );
        }

        return $this->titleForLocale(
            $locale,
        );
    }

    public function rankLabel(): string
    {
        return $this->rankOption()?->label()
            ?? '';
    }

    public function rankLabelForLocale(
        string $locale,
    ): string {
        $rank = $this->rankOption();

        if ($rank === null) {
            return '';
        }

        return $rank->labelForLocale(
            $locale,
        );
    }

    public function isCurrentAppointment(): bool
    {
        return $this->end_date === null;
    }

    /**
     * Legacy helper for the original four appointments.
     *
     * Dynamic code should query school_leadership_positions instead.
     *
     * @return list<string>
     */
    public static function allowedRoles(): array
    {
        return array_map(
            static fn (
                SchoolLeadershipAppointment $appointment,
            ): string => $appointment->value,
            SchoolLeadershipAppointment::cases(),
        );
    }

    public function supportsProfile(): bool
    {
        return $this->is_active
            && $this->position?->is_active;
    }

    public function hasPublicProfile(): bool
    {
        return $this->profile_enabled && $this->supportsProfile()
            && ($this->hasProfileContentForLocale('en') || $this->hasProfileContentForLocale('si'));
    }

    public function hasProfileContentForLocale(string $locale): bool
    {
        return in_array($locale, ['en', 'si'], true)
            && trim((string) $this->getAttribute('profile_html_'.$locale)) !== '';
    }

    public function profileUrlForLocale(string $locale): ?string
    {
        if (! $this->hasPublicProfile() || ! $this->hasProfileContentForLocale($locale)) {
            return null;
        }

        return $this->appointmentUrlForLocale($locale);
    }

    public function appointmentUrlForLocale(string $locale): ?string
    {
        if (! $this->supportsProfile() || ! in_array($locale, ['en', 'si'], true)) {
            return null;
        }

        return $locale === 'en'
            ? route('school-leadership.show', ['leader' => $this->id])
            : route('school-leadership.show.localized', ['locale' => $locale, 'leader' => $this->id]);
    }

    public function resolvedImageUrl(): ?string
    {
        if ($this->image) {
            return app(MediaUrlService::class)->mediumOrOriginal($this->image);
        }

        if (is_string($this->image_path) && trim($this->image_path) !== '') {
            return asset($this->image_path);
        }

        return null;
    }

    public function periodForLocale(string $locale = 'en'): string
    {
        if (! $this->start_date && ! $this->end_date) {
            return '';
        }

        $start = $this->start_date ? $this->start_date->format('Y - m - d') : '';
        $end = $this->end_date ? $this->end_date->format('Y - m - d') : ($locale === 'si' ? 'අද දක්වා' : 'Present');

        if ($start !== '') {
            return $locale === 'si'
                ? "{$start} සිට {$end} දක්වා"
                : "{$start} to {$end}";
        }

        return $end;
    }

    public function displayNameWithRankForLocale(string $locale = 'en'): string
    {
        $name = $this->nameForLocale($locale);
        $rank = $this->rankLabelForLocale($locale);

        if ($rank !== '' && ! str_starts_with(strtolower($name), strtolower($rank))) {
            return "{$rank} {$name}";
        }

        return $name !== '' ? $name : $this->titleForLocale($locale);
    }

    public function secondaryTenureForLocale(string $locale = 'en'): ?string
    {
        if ($locale === 'si' && is_string($this->secondary_tenure_si) && trim($this->secondary_tenure_si) !== '') {
            return $this->secondary_tenure_si;
        }

        return is_string($this->secondary_tenure_en) && trim($this->secondary_tenure_en) !== ''
            ? $this->secondary_tenure_en
            : null;
    }
}
