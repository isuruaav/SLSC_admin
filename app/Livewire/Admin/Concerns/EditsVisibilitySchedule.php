<?php

namespace App\Livewire\Admin\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

trait EditsVisibilitySchedule
{
    public string $startsAt = '';

    public string $endsAt = '';

    private function loadSchedule(Model $record): void
    {
        $this->startsAt = $record->getAttribute('starts_at') ? CarbonImmutable::parse($record->getAttribute('starts_at'))->setTimezone('Asia/Colombo')->format('Y-m-d\TH:i') : '';
        $this->endsAt = $record->getAttribute('ends_at') ? CarbonImmutable::parse($record->getAttribute('ends_at'))->setTimezone('Asia/Colombo')->format('Y-m-d\TH:i') : '';
    }

    /** @return array{starts_at: CarbonImmutable|null, ends_at: CarbonImmutable|null} */
    private function scheduleAttributes(): array
    {
        $this->validate(['startsAt' => ['nullable', 'date'], 'endsAt' => ['nullable', 'date', $this->startsAt !== '' ? 'after:startsAt' : 'date']]);

        return [
            'starts_at' => $this->startsAt !== '' ? CarbonImmutable::parse($this->startsAt, 'Asia/Colombo')->setTimezone(config('app.timezone')) : null,
            'ends_at' => $this->endsAt !== '' ? CarbonImmutable::parse($this->endsAt, 'Asia/Colombo')->setTimezone(config('app.timezone')) : null,
        ];
    }
}
