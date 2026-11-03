<?php

use App\Services\LivewireOutputEncoding;
use Illuminate\Support\Facades\Log;

it('preserves valid Livewire output and its signed snapshot exactly', function (): void {
    Log::spy();
    $snapshot = json_encode(['data' => ['title' => 'සිංහල தமிழ்'], 'checksum' => 'signed-value'], JSON_THROW_ON_ERROR);
    $payload = [
        'components' => [[
            'snapshot' => $snapshot,
            'effects' => ['html' => '<div>සිංහල தமிழ் 🏫</div>', 'returns' => [null, false, 1, 1.0]],
        ]],
        'assets' => [],
    ];

    expect(app(LivewireOutputEncoding::class)->repair($payload))->toBe($payload);
    Log::shouldNotHaveReceived('warning');
});

it('repairs invalid display bytes in nested effects and assets without changing state', function (): void {
    Log::spy();
    $snapshot = json_encode(['memo' => ['name' => 'admin.news.news-edit'], 'checksum' => 'signed-value'], JSON_THROW_ON_ERROR);
    $payload = [
        'components' => [[
            'snapshot' => $snapshot,
            'effects' => ['html' => "<div>සිංහල \x96</div>", 'returns' => ["bad\xFF" => ["name\xC3"]]],
        ]],
        'assets' => ['stylesheet' => "<style>/* \x80 */</style>"],
    ];

    $result = app(LivewireOutputEncoding::class)->repair($payload);

    expect($result['components'][0]['snapshot'])->toBe($snapshot)
        ->and($result['components'][0]['effects']['html'])->toBe("<div>සිංහල \u{FFFD}</div>")
        ->and($result['components'][0]['effects']['returns'])->toBe(["bad\u{FFFD}" => ["name\u{FFFD}"]])
        ->and($result['assets']['stylesheet'])->toBe("<style>/* \u{FFFD} */</style>")
        ->and(json_encode($result, JSON_THROW_ON_ERROR))->toBeString()
        ->and($payload['components'][0]['effects']['html'])->toBe("<div>සිංහල \x96</div>");

    Log::shouldHaveReceived('warning')->once()->with('Repaired malformed UTF-8 in Livewire output.', [
        'components' => ['admin.news.news-edit'],
        'repaired_strings' => 4,
    ]);
});
