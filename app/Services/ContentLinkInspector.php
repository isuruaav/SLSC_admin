<?php

namespace App\Services;

use App\Http\Controllers\PublicRedirectController;
use App\Models\Gallery;
use App\Models\News;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Publication;
use App\Models\Redirect;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Throwable;

final class ContentLinkInspector
{
    /** @return array{url: string, status: string, message: string} */
    public function inspect(string $url): array
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $result = fn (string $status, string $message): array => ['url' => $url, 'status' => $status, 'message' => $message];
        if ($url === '' || str_contains($url, '[[')) {
            return $result('unchecked', 'CMS placeholder: check the generated public link.');
        }
        if (str_starts_with($url, '#')) {
            return $result('ok', 'Link within the current page.');
        }
        if (preg_match('/^(mailto:|tel:)/i', $url)) {
            return $result('ok', 'Contact link.');
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme !== null && ! in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            return $result('broken', 'Unsupported link protocol.');
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (is_string($host) && strcasecmp($host, (string) parse_url(config('app.url'), PHP_URL_HOST)) !== 0) {
            return $result('external', 'External website: open the link to verify availability.');
        }
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $base = rtrim((string) parse_url(config('app.url'), PHP_URL_PATH), '/');
        if ($base !== '' && ($path === $base || str_starts_with($path, $base.'/'))) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }
        try {
            $route = Route::getRoutes()->match(Request::create($path, 'GET'));
            $name = (string) $route->getName();
            if (str_starts_with($name, 'admin.') || str_starts_with($path, '/admin')) {
                return $result('private', 'Requires an administrator account.');
            }
            $slug = $route->parameter('slug');
            $locale = $route->parameter('locale', 'en');
            if (is_string($slug)) {
                $exists = match (true) {
                    str_starts_with($name, 'pages.') => Page::query()->published()->where('slug', $slug)->where('locale', $locale)->exists(),
                    str_starts_with($name, 'news.') => News::query()->published()->where('slug', $slug)->where('locale', $locale)->exists(),
                    str_starts_with($name, 'galleries.') => Gallery::query()->published()->where('slug', $slug)->exists(),
                    str_starts_with($name, 'documents.'), str_starts_with($name, 'document-files.') => app(DocumentUploadManager::class)->publicQuery(is_string($locale) ? $locale : 'en')->where('slug', $slug)->exists(),
                    str_starts_with($name, 'organization.') => Organization::query()->where('slug', $slug)->where('status', 'published')->exists(),
                    default => true,
                };
                if (! $exists) {
                    return $result('broken', 'The target is missing or is not published.');
                }
            }
            if (str_starts_with($name, 'publications.') && $route->parameter('publication')) {
                if (! Publication::query()->published()->where('uuid', $route->parameter('publication'))->exists()) {
                    return $result('broken', 'Publication is missing or is not published.');
                }
            }
            if ($route->getActionName() === PublicRedirectController::class || str_contains($route->getActionName(), 'PublicRedirectController')) {
                $redirect = Redirect::query()->where('source_path', $path)->where('is_active', true)->first();
                if (! $redirect) {
                    return $result('broken', 'No published page or redirect exists at this address.');
                }

                return $result('redirect', 'Redirects to '.$redirect->destination_url);
            }

            return $result('ok', 'Internal target is available.');
        } catch (Throwable) {
            return $result('broken', 'No public route matches this address.');
        }
    }

    /** @return list<array{url: string, status: string, message: string}> */
    public function inspectHtml(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument;
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            $urls = [];
            foreach ($document->getElementsByTagName('a') as $link) {
                $href = $link->getAttribute('href');
                if ($href !== '' && ! in_array($href, $urls, true)) {
                    $urls[] = $href;
                }
                if (count($urls) >= 100) {
                    break;
                }
            }

            return array_map(fn (string $url): array => $this->inspect($url), $urls);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
