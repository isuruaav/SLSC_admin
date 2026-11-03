<?php

use App\Services\ErrorPageRenderer;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');
});

it('renders a friendly page for an unknown address with the real 404 status', function (): void {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee('We couldn’t find that page.')
        ->assertSee('data-error-home', false)
        ->assertSee('href="http://localhost"', false)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertDontSee('Stack trace')
        ->assertDontSee('/build/assets/', false)
        ->assertDontSee('<script', false);
});

it('localizes unknown paths before a controller or locale middleware can run', function (string $locale, string $title): void {
    $this->get('/'.$locale.'/this-page-does-not-exist')->assertNotFound()
        ->assertSee('lang="'.$locale.'"', false)
        ->assertSee($title)
        ->assertSee('href="http://localhost"', false);
})->with([
    ['en', 'We couldn’t find that page.'],
    ['si', 'මෙම පිටුව සොයාගත නොහැකියි.'],
    ['ta', 'இந்தப் பக்கத்தைக் கண்டுபிடிக்க முடியவில்லை.'],
]);

it('shows a safe recovery page for server exceptions even with debug enabled', function (bool $debug): void {
    config()->set('app.debug', $debug);
    Route::get('/__error-test/server', static fn () => throw new RuntimeException('Private SQL password and file path must not appear'));

    $this->get('/__error-test/server')->assertStatus(500)
        ->assertSee('Something went wrong.')
        ->assertSee('data-error-retry', false)
        ->assertDontSee('Private SQL password')
        ->assertDontSee('RuntimeException')
        ->assertDontSee('Stack trace');
})->with([true, false]);

it('keeps HTTP status codes and exception headers while replacing their technical message', function (int $status): void {
    Route::get('/__error-test/status', static fn () => abort($status, 'Private exception detail', ['Retry-After' => '45', 'X-Error-Test' => 'retained']));

    $this->get('/__error-test/status')->assertStatus($status)
        ->assertHeader('Retry-After', '45')
        ->assertHeader('X-Error-Test', 'retained')
        ->assertSee('data-error-home', false)
        ->assertSee('Error '.$status)
        ->assertDontSee('Private exception detail');
})->with([401, 403, 405, 408, 410, 419, 422, 429, 451, 500, 502, 503, 504, 599]);

it('preserves the allowed methods header on a real method-not-allowed error', function (): void {
    Route::get('/__error-test/get-only', static fn () => response('Page'));
    $this->post('/__error-test/get-only')->assertStatus(405)->assertHeader('Allow', 'GET, HEAD')->assertSee('Let’s open that page again.');
});

it('handles expired forms without providing a retry link that could resubmit them', function (): void {
    Route::post('/__error-test/expired', static fn () => throw new TokenMismatchException('Sensitive token mismatch details'));

    $this->withHeaders(['Referer' => 'http://localhost/contact'])->post('/__error-test/expired', ['message' => 'Private form text'])
        ->assertStatus(419)
        ->assertSee('Your session has expired.')
        ->assertSee('data-error-back', false)
        ->assertDontSee('data-error-retry', false)
        ->assertDontSee('Sensitive token mismatch')
        ->assertDontSee('Private form text');
});

it('renders a server error if the database cannot be reached without loading site settings', function (): void {
    config()->set('app.debug', false);
    Schema::shouldReceive('hasTable')->with('site_settings')->andThrow(new RuntimeException('Database connection failed: private credentials'));

    $this->get('/')->assertStatus(500)->assertSee('Something went wrong.')
        ->assertDontSee('private credentials')->assertSee('data-error-home', false);
});

it('does not require database queries or a Vite manifest to render a recovery page', function (): void {
    $queries = [];
    DB::listen(static function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });
    $request = Request::create('http://localhost/missing');
    $response = app(ErrorPageRenderer::class)->render($request, new Response('Technical error', 500));

    expect($queries)->toBe([])
        ->and($response->getStatusCode())->toBe(500)
        ->and($response->getContent())->toContain('Something went wrong.')->not->toContain('/build/assets/');
});

it('only offers a back link to another page on the same site', function (string $referer, bool $allowed): void {
    $response = $this->withHeaders(['Referer' => $referer])->get('/this-page-does-not-exist')->assertNotFound();
    if ($allowed) {
        $response->assertSee('data-error-back', false)->assertSee('href="http://localhost/news"', false);
    } else {
        $response->assertDontSee('data-error-back', false);
    }
})->with([
    ['http://localhost/news', true],
    ['https://external.example/news', false],
    ['http://localhost.evil.example/news', false],
    ['javascript:alert(1)', false],
    ['//external.example/news', false],
    ['http://localhost/this-page-does-not-exist', false],
]);

it('keeps recovery links and Sinhala detection correct when installed in a subdirectory', function (): void {
    $request = Request::create('http://localhost/school/public/si/missing', 'GET', [], [], [], [
        'SCRIPT_NAME' => '/school/public/index.php',
        'PHP_SELF' => '/school/public/index.php',
        'SCRIPT_FILENAME' => public_path('index.php'),
        'HTTP_REFERER' => 'http://localhost/school/public/news',
    ]);
    app()->instance('request', $request);
    app('url')->setRequest($request);
    $response = app(ErrorPageRenderer::class)->render($request, new Response('', 404));

    expect($response->getContent())->toContain('lang="si"', 'href="http://localhost/school/public"', 'href="http://localhost/school/public/news"')
        ->not->toContain('href="http://localhost"');
});

it('keeps HEAD responses empty', function (): void {
    $this->head('/this-page-does-not-exist')->assertNotFound()->assertContent('');
});

it('preserves JSON and API error formats', function (): void {
    config()->set('app.debug', false);
    $this->getJson('/this-page-does-not-exist')->assertNotFound()->assertJsonStructure(['message'])->assertDontSee('error-card');
    $this->get('/api/this-page-does-not-exist')->assertNotFound()->assertJsonStructure(['message'])->assertDontSee('error-card');
});

it('leaves Livewire responses and non-error responses intact', function (): void {
    $response = new Response('Original Livewire error', 419, ['Content-Type' => 'text/html']);
    $request = Request::create('http://localhost/livewire/update', 'POST', [], [], [], ['HTTP_X_LIVEWIRE' => '1']);
    expect(app(ErrorPageRenderer::class)->render($request, $response))->toBe($response);

    $success = new Response('OK', 200);
    expect(app(ErrorPageRenderer::class)->render(Request::create('/'), $success))->toBe($success);
});

it('does not replace validation redirects with a full-page error', function (): void {
    Route::post('/__error-test/validation', static fn () => throw ValidationException::withMessages(['name' => 'A name is required.']))->middleware('web');
    $this->from('/contact')->post('/__error-test/validation')->assertRedirect('/contact')->assertSessionHasErrors('name');
});
