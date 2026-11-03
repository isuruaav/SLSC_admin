<?php

use App\Http\Middleware\EnsureSiteIsPublic;
use App\Http\Middleware\EnsureSiteLanguageIsEnabled;
use App\Http\Middleware\EnsureUserIsActive;
use App\Services\ErrorPageRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'site.public' => EnsureSiteIsPublic::class,
        ]);

        $middleware->web(append: [
            EnsureSiteIsPublic::class,
            EnsureSiteLanguageIsEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->respond(
            static fn (Response $response, Throwable $exception, Request $request): Response => app(ErrorPageRenderer::class)->render($request, $response),
        );
    })
    ->create();
