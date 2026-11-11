<?php

use App\Enums\PageStatus;
use App\Models\Page;

test('published page can be viewed using its public slug', function (): void {
    $page = Page::factory()
        ->published()
        ->create([
            'title' => 'About Our Unit',
            'slug' => 'about-our-unit',
            'excerpt' => 'Official information about our unit.',
            'content' => 'This is the official page content.',
        ]);

    $this->get(
        route('pages.show', $page->slug),
    )
        ->assertOk()
        ->assertSee('About Our Unit')
        ->assertSee('Official information about our unit.')
        ->assertSee('This is the official page content.');
});

test('draft page is not publicly accessible', function (): void {
    $page = Page::factory()->create([
        'slug' => 'draft-page',
        'status' => PageStatus::Draft->value,
    ]);

    $this->get(
        route('pages.show', $page->slug),
    )->assertNotFound();
});

test('submitted page is not publicly accessible', function (): void {
    $page = Page::factory()
        ->submitted()
        ->create([
            'slug' => 'submitted-page',
        ]);

    $this->get(
        route('pages.show', $page->slug),
    )->assertNotFound();
});

test('future scheduled publication is not publicly accessible', function (): void {
    $page = Page::factory()
        ->published()
        ->create([
            'slug' => 'future-page',
            'published_at' => now()->addDay(),
        ]);

    $this->get(
        route('pages.show', $page->slug),
    )->assertNotFound();
});

test('soft deleted published page is not publicly accessible', function (): void {
    $page = Page::factory()
        ->published()
        ->create([
            'slug' => 'deleted-public-page',
        ]);

    $page->delete();

    $this->get(
        route('pages.show', $page->slug),
    )->assertNotFound();
});
test('sanitized page html is rendered publicly', function (): void {
    $page = Page::factory()
        ->published()
        ->create([
            'slug' => 'formatted-content-page',

            'content' => '<h2>Public heading</h2>'
                .'<p onclick="alert(\'unsafe-click\')">'
                .'This is <strong>important</strong>.'
                .'</p>'
                .'<script>alert("unsafe-script")</script>',
        ]);

    $this->get(
        route('pages.show', $page->slug),
    )
        ->assertOk()
        ->assertSee(
            '<h2>Public heading</h2>',
            false,
        )
        ->assertSee(
            '<strong>important</strong>',
            false,
        )
        ->assertDontSee(
            'unsafe-script',
            false,
        )
        ->assertDontSee(
            'unsafe-click',
            false,
        )
        ->assertDontSee(
            'onclick=',
            false,
        );
});

test('root relative media and document links are preserved or prefixed in page html', function (): void {
    $page = Page::factory()
        ->published()
        ->create([
            'slug' => 'media-links-page',
            'content' => '<p><a href="/document-files/en/test-doc/view"><img src="/media-files/cover.jpg" alt="Cover"></a></p>',
        ]);

    $this->get(route('pages.show', $page->slug))
        ->assertOk()
        ->assertSee('document-files/en/test-doc/view')
        ->assertSee('media-files/cover.jpg');
});
