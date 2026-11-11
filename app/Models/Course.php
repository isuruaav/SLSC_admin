<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $end_date
 */
final class Course extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<CourseQualification, $this> */
    public function qualifications(): HasMany
    {
        return $this->hasMany(CourseQualification::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return BelongsTo<CourseCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }
}
