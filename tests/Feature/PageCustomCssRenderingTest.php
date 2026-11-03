<?php

use App\Models\Page;
use Illuminate\Support\Facades\DB;

it('renders scoped styles for the correct language and editor', function (string $locale, string $mode, ?string $theme): void {
    config(['awcms.active_theme' => $theme]);

    $page = Page::factory()->published()->create([
        'slug' => 'custom-courses',
        'locale' => $locale,
        'editor_mode' => $mode,
        'content' => '<h3 class="sos-course-card">Courses / පාඨමාලා</h3>',
        'custom_css' => '.sos-course-card { color: #ffffff; } @media (max-width: 767px) { .sos-course-card { font-size: 15px; } }',
    ]);
    Page::factory()->published()->create(['custom_css' => '.unrelated-page { color: red; }']);

    $this->get(route('pages.show.localized', ['locale' => $locale, 'slug' => $page->slug]))
        ->assertOk()
        ->assertSee('id="cms-page-'.$page->id.'"', false)
        ->assertSee(':where(#cms-page-'.$page->id.') .sos-course-card', false)
        ->assertSee('@media (max-width: 767px)', false)
        ->assertDontSee('.unrelated-page', false)
        ->assertSee('පාඨමාලා');
})->with(['en', 'si'])->with(['html', 'visual'])->with(['slsc', null]);

it('omits invalid CSS from a manually modified database record', function (): void {
    $page = Page::factory()->published()->create(['content' => '<p>Safe page</p>']);
    DB::table('pages')->where('id', $page->id)->update(['custom_css' => '</style><script>bad-css-script()</script>']);

    $this->get(route('pages.show', $page->slug))
        ->assertOk()->assertSee('Safe page')->assertDontSee('bad-css-script', false);
});
