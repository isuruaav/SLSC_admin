<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasVisibilitySchedule
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public function scopeVisibleNow(Builder $query): void
    {
        $query->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }
}
