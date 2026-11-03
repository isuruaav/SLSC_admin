<?php

use App\Http\Controllers\LivewireUpdateController;
use App\Livewire\Admin\News\NewsCreate;
use App\Models\User;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleComponents\CorruptComponentPayloadException;
use Livewire\Mechanisms\HandleRequests\HandleRequests;
use Livewire\Mechanisms\HandleRequests\RequireLivewireHeaders;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(function (): void {
    $user = User::factory()->create();
    Permission::findOrCreate('news.create', 'web');
    $user->givePermissionTo('news.create');
    $this->actingAs($user);
});

it('keeps the web and Livewire header middleware on the custom update endpoint', function (): void {
    $route = app('router')->getRoutes()->getByName('livewire.update');

    expect($route)->not->toBeNull()
        ->and($route->getActionName())->toBe(LivewireUpdateController::class)
        ->and($route->middleware())->toContain('web', RequireLivewireHeaders::class);

    $this->postJson(app('livewire')->getUpdateUri(), ['components' => []])->assertNotFound();
    $this->withHeaders(['X-Livewire' => '1'])->post(app('livewire')->getUpdateUri(), ['components' => []])->assertNotFound();
});

it('continues rejecting a modified Livewire snapshot', function (): void {
    $component = Livewire::test(NewsCreate::class);
    $snapshot = $component->snapshot;
    $snapshot['data']['title'] = 'Modified outside Livewire';
    $component->snapshot = $snapshot;

    expect(fn () => $component->set('summary', 'Tampered request'))->toThrow(CorruptComponentPayloadException::class);
});

it('passes streamed Livewire responses through unchanged', function (): void {
    $stream = new StreamedResponse(static fn () => null);
    $handler = Mockery::mock(HandleRequests::class);
    $handler->shouldReceive('handleUpdate')->once()->andReturn($stream);

    expect((new LivewireUpdateController)($handler))->toBe($stream);
});

it('encodes the complete Livewire response safely at the HTTP boundary', function (): void {
    $component = Livewire::test(NewsCreate::class);
    // A response extension can add output after the earlier repair hook.
    // This also exercises JSON-serializable objects rather than just arrays.
    $stop = Livewire::listen('response', static fn () => static function (array $payload): array {
        $payload['components'][0]['effects']['hostingFixture'] = (object) ['label' => "legacy\x96"];

        return $payload;
    });

    try {
        $component->set('title', 'සිංහල தமிழ்')->assertHasNoErrors()->assertSet('title', 'සිංහල தமிழ்');
        expect($component->effects['hostingFixture']['label'])->toBe("legacy\u{FFFD}");
        // A second request must still accept the signed snapshot from the first.
        $component->set('summary', 'Second request')->assertHasNoErrors()->assertSet('title', 'සිංහල தமிழ்');
    } finally {
        $stop();
    }
});
