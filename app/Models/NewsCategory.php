<?php

namespace App\Models;

use Database\Factories\NewsCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class NewsCategory extends Model
{
    /** @use HasFactory<NewsCategoryFactory> */
    use HasFactory;

    use SoftDeletes;

    public function newsType(): string
    {
        return $this->slug === 'photo-story' ? 'photo_story' : 'news_feature';
    }

    /**
     * @var list<string>
     */
    protected $guarded = [
        'id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by',
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by',
        );
    }

    /**
     * @return HasMany<News, $this>
     */
    public function news(): HasMany
    {
        return $this->hasMany(
            News::class,
            'category_id',
        );
    }

    /**
     * @param  Builder<NewsCategory>  $query
     * @return Builder<NewsCategory>
     */
    public function scopeActive(
        Builder $query,
    ): Builder {
        return $query->where(
            'is_active',
            true,
        );
    }

    /**
     * @param  Builder<NewsCategory>  $query
     * @return Builder<NewsCategory>
     */
    public function scopeOrdered(
        Builder $query,
    ): Builder {
        return $query
            ->orderBy(
                'sort_order',
            )
            ->orderBy(
                'name',
            );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',

            'sort_order' => 'integer',
        ];
    }
}
