<?php

namespace App\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ErrorPageRenderer
{
    public function render(Request $request, Response $response): Response
    {
        $status = $response->getStatusCode();
        $contentType = strtolower($response->headers->get('Content-Type', ''));

        if ($status < 400 || $status > 599
            || $request->expectsJson()
            || $request->is('api/*')
            || $request->headers->has('X-Livewire')
            || ($contentType !== '' && ! str_contains($contentType, 'text/html'))) {
            return $response;
        }

        $locale = $request->route('locale');
        if (! is_string($locale) || ! in_array($locale, ['en', 'si', 'ta'], true)) {
            $locale = $request->segment(1);
        }
        if (! is_string($locale) || ! in_array($locale, ['en', 'si', 'ta'], true)) {
            $locale = app()->getLocale();
        }
        $locale = in_array($locale, ['en', 'si', 'ta'], true) ? $locale : 'en';

        $messageKey = in_array($status, [401, 403, 404, 405, 408, 410, 419, 422, 429, 500, 502, 503, 504], true)
            ? (string) $status : ($status >= 500 ? 'server' : 'client');

        // Recovery must work even if the database, theme or Vite manifest is unavailable.
        // English is always enabled, so the root homepage cannot link to a disabled locale.
        $homeUrl = url('/');
        $referer = $request->headers->get('referer');
        $baseUrl = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');
        $backUrl = is_string($referer)
            && ($referer === $baseUrl || str_starts_with($referer, $baseUrl.'/'))
            && $referer !== $request->fullUrl()
                ? $referer : null;

        $headers = $response->headers->all();
        unset($headers['content-type'], $headers['content-length'], $headers['content-encoding'], $headers['etag'], $headers['last-modified']);

        return response()->view('errors.friendly', [
            'status' => $status,
            'errorLocale' => $locale,
            'messageKey' => $messageKey,
            'homeUrl' => $homeUrl,
            'backUrl' => $backUrl,
            'retryUrl' => $request->isMethodSafe() && in_array($status, [408, 429, 500, 502, 503, 504], true)
                ? $request->fullUrl() : null,
            'showSignIn' => in_array($status, [401, 403, 419], true),
        ], $status, $headers)
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->prepare($request);
    }
}
