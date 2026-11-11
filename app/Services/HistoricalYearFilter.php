<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class HistoricalYearFilter
{
    /**
     * @template TModel of Model
     *
     * @param  Collection<int,TModel>  $records
     * @return Collection<int,TModel>
     */
    public function apply(Collection $records, string $year, string $view = 'grid'): Collection
    {
        $selected = [];
        foreach ($records as $record) {
            if ($year === '' || $this->includesYear($record, (int) $year)) {
                $selected[] = $record;
            }
        }
        if ($view === 'timeline') {
            usort($selected, fn (Model $a, Model $b): int => $this->startOrder($a) <=> $this->startOrder($b));
        }

        return new Collection($selected);
    }

    private function includesYear(Model $record, int $year): bool
    {
        $startDate = $record->getAttribute('start_date');
        $endDate = $record->getAttribute('end_date');
        $start = $startDate instanceof DateTimeInterface ? (int) $startDate->format('Y') : null;
        $end = $endDate instanceof DateTimeInterface ? (int) $endDate->format('Y') : null;
        preg_match_all('/\b(19\d{2}|20\d{2})\b/', (string) $record->getAttribute('period_en'), $matches);
        $years = array_map('intval', $matches[0]);
        $start ??= $years[0] ?? null;
        $end ??= count($years) > 1 ? max($years) : ($start !== null && $startDate !== null ? (int) now()->format('Y') : $start);

        return $start !== null && $year >= $start && $year <= ($end ?? $start);
    }

    private function startOrder(Model $record): int
    {
        $date = $record->getAttribute('start_date');
        if ($date instanceof DateTimeInterface) {
            return (int) $date->format('Ymd');
        }
        preg_match('/\b(19\d{2}|20\d{2})\b/', (string) $record->getAttribute('period_en'), $matches);

        return isset($matches[0]) ? (int) $matches[0] * 10000 : PHP_INT_MAX;
    }
}
