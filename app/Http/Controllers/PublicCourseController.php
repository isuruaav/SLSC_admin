<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class PublicCourseController extends Controller
{
    public function index(Request $request, string $locale = 'en'): View
    {
        abort_unless(in_array($locale, ['en', 'si'], true), 404);
        app()->setLocale($locale);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'category' => ['nullable', 'integer', 'exists:course_categories,id'], 'status' => ['nullable', 'in:all,upcoming,ongoing,completed']]);
        $search = trim($filters['q'] ?? '');
        $status = $filters['status'] ?? 'all';
        $category = $filters['category'] ?? '';

        return view('public.courses.index', [
            'courses' => Course::query()
                ->where('is_active', true)
                ->with(['qualifications', 'category'])
                ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('name_si', 'like', '%'.$search.'%')))
                ->when($category !== '', fn ($query) => $query->where('course_category_id', $category))
                ->when($status === 'upcoming', fn ($query) => $query->whereDate('start_date', '>', today()))
                ->when($status === 'completed', fn ($query) => $query->whereDate('end_date', '<', today()))
                ->when($status === 'ongoing', fn ($query) => $query->whereDate('start_date', '<=', today())->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today())))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(12)->withQueryString(),
            'search' => $search, 'selectedCategory' => $category, 'selectedStatus' => $status,
            'categories' => CourseCategory::query()->orderBy('sort_order')->get(),
            'currentLocale' => $locale,
            'languageVersions' => [
                ['code' => 'en', 'available' => true, 'url' => route('courses.index')],
                ['code' => 'si', 'available' => true, 'url' => route('courses.index.localized', ['locale' => 'si'])],
            ],
        ]);
    }
}
