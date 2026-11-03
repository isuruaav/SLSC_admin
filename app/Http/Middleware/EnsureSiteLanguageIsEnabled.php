<?php

namespace App\Http\Middleware;

use App\Services\SiteLanguageService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSiteLanguageIsEnabled
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if (is_string($locale) && ! $request->routeIs('admin.*')) {
            abort_unless(app(SiteLanguageService::class)->enabled($locale), 404);
        }

        return $next($request);
    }
}
