<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NewsEditorMode;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicNewsController;
use App\Models\News;
use App\Services\PageHtmlSanitizer;
use App\Services\SiteUrlService;
use Illuminate\Contracts\View\View;

final class NewsPreviewController extends Controller
{
    public function __invoke(News $news): View
    {
        $news->load([
            'category',
            'featuredImage.variants',
            'images.media.variants',
        ]);

        $content = $news->getAttribute('content');

        $rawContent = is_string($content)
            ? $content
            : null;

        $rawEditorMode = $news->getRawOriginal('editor_mode');

        $editorMode = is_string($rawEditorMode)
            ? NewsEditorMode::tryFrom($rawEditorMode)
            : null;

        $editorMode ??= NewsEditorMode::Visual;

        $sanitizer = app(PageHtmlSanitizer::class);

        $safeContent = $editorMode === NewsEditorMode::Visual
            ? $sanitizer->sanitizeVisual($rawContent)
            : $sanitizer->sanitize($rawContent);
        $safeContent = app(SiteUrlService::class)->resolveHtml($safeContent);

        return view(
            'admin.news.preview',
            [
                'news' => $news,
                'safeContent' => $safeContent,
                'featuredImageUrl' => PublicNewsController::imageUrl(
                    $news->featuredImage,
                ),
                'pageTitle' => 'Preview: '.$news->title,
                'metaDescription' => $news->summary ?: $news->title,
                'robots' => 'noindex,nofollow',
            ],
        );
    }
}
