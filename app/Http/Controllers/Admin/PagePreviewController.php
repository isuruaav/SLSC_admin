<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageEditorMode;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\PageBlockRenderer;
use App\Services\PageHtmlSanitizer;
use App\Services\SiteUrlService;
use App\Support\PageSeo;
use Illuminate\Contracts\View\View;

final class PagePreviewController extends Controller
{
    public function __invoke(
        Page $page,
    ): View {
        $pageLocale = $page->getRawOriginal('locale');

        if (
            is_string($pageLocale)
            && in_array($pageLocale, ['en', 'si', 'ta'], true)
        ) {
            app()->setLocale($pageLocale);
        }

        $sanitizer = app(PageHtmlSanitizer::class);
        $content = is_string($page->content) ? $page->content : null;
        $safeContent = $page->getRawOriginal('editor_mode') === PageEditorMode::Visual->value
            ? $sanitizer->sanitizeVisual($content)
            : $sanitizer->sanitize($content);

        $safeContent = app(SiteUrlService::class)->resolveHtml($safeContent);

        $pageBlocks = app(
            PageBlockRenderer::class,
        )->forPage(
            $page,
        );

        return view(
            'admin.pages.preview',
            [
                'page' => $page,

                'safeContent' => $safeContent,

                'pageBlocks' => $pageBlocks,

                'pageTitle' => 'Preview: '.PageSeo::title(
                    $page,
                ),

                'metaDescription' => PageSeo::description(
                    $page,
                ),

                /*
                 * Admin preview is never indexed.
                 */
                'robots' => 'noindex,nofollow',

                'canonicalUrl' => null,

                /*
                 * Administrative previews should
                 * not expose public social metadata.
                 */
                'socialMetadata' => false,
            ],
        );
    }
}
