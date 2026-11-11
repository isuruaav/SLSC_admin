<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\News;
use App\Models\Page;
use App\Models\Publication;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class DashboardController
{
    public function __invoke(): View
    {
        $drafts = collect();
        if (Gate::allows('pages.view') && Gate::allows('pages.update')) {
            $drafts = $drafts->concat(Page::query()->where('status', 'draft')->latest('updated_at')->limit(5)->get()->map(fn (Page $page) => [
                'title' => $page->title, 'type' => 'Page', 'updated_at' => $page->updated_at, 'url' => route('admin.pages.edit', $page),
            ]));
        }
        if (Gate::allows('news.view') && Gate::allows('news.update')) {
            $drafts = $drafts->concat(News::query()->whereIn('status', ['draft', 'changes_requested'])->latest('updated_at')->limit(5)->get()->map(fn (News $news) => [
                'title' => $news->title, 'type' => 'News', 'updated_at' => $news->updated_at, 'url' => route('admin.news.edit', $news),
            ]));
        }
        if (Gate::allows('documents.view') && Gate::allows('documents.update')) {
            $drafts = $drafts->concat(Publication::query()->where('is_published', false)->latest('updated_at')->limit(5)->get()->map(fn (Publication $publication) => [
                'title' => $publication->title, 'type' => 'Publication', 'updated_at' => $publication->updated_at, 'url' => route('admin.publications.index', ['edit' => $publication->id]),
            ]));
        }

        $workNotices = [];
        if (Gate::allows('pages.view')) {
            $workNotices[] = ['label' => 'Pages waiting for review', 'count' => Page::query()->where('status', 'submitted')->count(), 'url' => route('admin.pages.index')];
        }
        if (Gate::allows('news.view')) {
            $workNotices[] = ['label' => 'News waiting for review', 'count' => News::query()->where('status', 'submitted')->count(), 'url' => route('admin.news.index')];
            $workNotices[] = ['label' => 'News articles without a featured image', 'count' => News::query()->whereIn('status', ['draft', 'submitted', 'published'])->whereNull('featured_image_id')->count(), 'url' => route('admin.news.index')];
            $workNotices[] = ['label' => 'English news without a Sinhala version', 'count' => News::query()->where('locale', 'en')->whereNotExists(fn ($query) => $query->selectRaw('1')->from('news as translated')->whereColumn('translated.translation_group', 'news.translation_group')->where('translated.locale', 'si')->whereNull('translated.deleted_at'))->count(), 'url' => route('admin.news.index')];
        }
        if (Gate::allows('pages.view')) {
            $workNotices[] = ['label' => 'English pages without a Sinhala version', 'count' => Page::query()->where('locale', 'en')->whereNotExists(fn ($query) => $query->selectRaw('1')->from('pages as translated')->whereColumn('translated.translation_group', 'pages.translation_group')->where('translated.locale', 'si')->whereNull('translated.deleted_at'))->count(), 'url' => route('admin.pages.index')];
        }

        return view('admin.dashboard', [
            'workNotices' => $workNotices,
            'pageCount' => Page::query()->count(),
            'newsCount' => News::query()->count(),
            'galleryCount' => Gallery::query()->count(),
            'documentCount' => Document::query()->count(),
            'publicationCount' => Publication::query()->count(),
            'drafts' => $drafts->sortByDesc('updated_at')->take(5)->values(),
            'newContactCount' => ContactMessage::query()->where('status', 'new')->count(),
            'recentActivity' => AuditLog::query()->latest('created_at')->limit(8)->get(),
        ]);
    }
}
