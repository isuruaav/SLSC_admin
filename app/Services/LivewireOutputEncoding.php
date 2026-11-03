<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

final class LivewireOutputEncoding
{
    /**
     * Repair display output before Livewire hands it to Laravel's JSON response.
     * Signed snapshots and stored content must remain untouched.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function repair(array $payload): array
    {
        $repaired = 0;
        $components = [];

        foreach ($payload['components'] ?? [] as $index => $component) {
            $before = $repaired;

            if (isset($component['effects'])) {
                $payload['components'][$index]['effects'] = $this->repairValue($component['effects'], $repaired);
            }

            if ($repaired > $before) {
                $snapshot = json_decode($component['snapshot'] ?? '{}', true);
                $components[] = $snapshot['memo']['name'] ?? 'unknown';
            }
        }

        if (isset($payload['assets'])) {
            $payload['assets'] = $this->repairValue($payload['assets'], $repaired);
        }

        if ($repaired > 0) {
            // Log identifiers only, never article HTML, filenames or file bytes.
            Log::warning('Repaired malformed UTF-8 in Livewire output.', [
                'components' => array_values(array_unique($components)),
                'repaired_strings' => $repaired,
            ]);
        }

        return $payload;
    }

    private function repairValue(mixed $value, int &$repaired): mixed
    {
        if (is_string($value)) {
            if (mb_check_encoding($value, 'UTF-8')) {
                return $value;
            }

            $repaired++;

            return json_decode(json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR), flags: JSON_THROW_ON_ERROR);
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$this->repairValue($key, $repaired)] = $this->repairValue($item, $repaired);
            }

            return $result;
        }

        return $value;
    }
}
