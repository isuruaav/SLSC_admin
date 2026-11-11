<?php

use App\Enums\PageEditorMode;
use App\Enums\PageStatus;
use App\Models\Page;
use App\Services\PageHtmlSanitizer;

it('preserves safe tailwind classes in page html', function (): void {
    $html = <<<'HTML'
<div class="mx-auto max-w-6xl px-6 py-12 md:grid md:grid-cols-2 md:gap-8">
    <h2 class="text-3xl font-bold text-emerald-700">About Us</h2>
    <p class="mt-4 text-zinc-600">Official page content.</p>
</div>
HTML;

    $cleanHtml = app(
        PageHtmlSanitizer::class,
    )->sanitize($html);

    expect($cleanHtml)
        ->toContain('mx-auto max-w-6xl px-6 py-12')
        ->toContain('md:grid md:grid-cols-2 md:gap-8')
        ->toContain('text-3xl font-bold text-emerald-700');
});

it('removes active content while keeping safe page html', function (): void {
    $html = <<<'HTML'
<div class="rounded-2xl bg-white p-6" onclick="alert('unsafe')">
    <script>alert('unsafe-script')</script>
    <iframe src="https://example.com"></iframe>
    <p style="color:red">Safe text</p>
</div>
HTML;

    $cleanHtml = app(
        PageHtmlSanitizer::class,
    )->sanitize($html);

    expect($cleanHtml)
        ->toContain('rounded-2xl bg-white p-6')
        ->toContain('Safe text')
        ->not->toContain('onclick')
        ->not->toContain('<script')
        ->not->toContain('<iframe')
        ->not->toContain('style=');
});

it('page model stores sanitized tailwind html', function (): void {
    $page = Page::factory()->create([
        'content' => '<div class="grid gap-6 md:grid-cols-2">'
            .'<p class="text-zinc-700">Safe content</p>'
            .'<script>alert("unsafe")</script>'
            .'</div>',
    ]);

    $page->refresh();

    expect($page->content)
        ->toContain('grid gap-6 md:grid-cols-2')
        ->toContain('text-zinc-700')
        ->not->toContain('<script');
});

it('published page renders sanitized tailwind html', function (): void {
    $page = Page::factory()
        ->published()
        ->create([
            'status' => PageStatus::Published->value,
            'slug' => 'tailwind-content-page',
            'content' => '<div class="mx-auto max-w-7xl px-6">'
                .'<h2 class="text-4xl font-bold">Tailwind Content</h2>'
                .'</div>',
        ]);

    $this->get(
        route('pages.show', $page->slug),
    )
        ->assertOk()
        ->assertSee(
            'class="mx-auto max-w-7xl px-6"',
            false,
        )
        ->assertSee('Tailwind Content');
});

it('visual editor preserves only its safe inline formatting styles', function (): void {
    $html = <<<'HTML'
<p style="color:#1d4ed8; background-color:#fef3c7; text-align:center; position:fixed" onclick="alert(1)">
    Visual formatted text
</p>
HTML;

    $cleanHtml = app(
        PageHtmlSanitizer::class,
    )->sanitizeVisual($html);

    expect($cleanHtml)
        ->toContain('Visual formatted text')
        ->toContain('color:')
        ->toContain('background-color:')
        ->toContain('text-align:')
        ->not->toContain('position:')
        ->not->toContain('onclick');
});

it('visual page model keeps safe visual styles after saving', function (): void {
    $page = Page::factory()->create([
        'editor_mode' => PageEditorMode::Visual->value,
        'content' => '<p style="color:#047857; background-color:#ecfdf5; text-align:right">Styled visual content</p>',
    ]);

    $page->refresh();

    expect($page->content)
        ->toContain('Styled visual content')
        ->toContain('color:')
        ->toContain('background-color:')
        ->toContain('text-align:');
});

it('preserves image layout and alignment styles in visual editor', function (): void {
    $html = '<p><img src="/media-files/slsc.jpg" alt="flag" style="float: left; margin: 0 1rem 1rem 0; max-width: 50%;" />Signal Corps Flag</p>';

    $cleanHtml = app(
        PageHtmlSanitizer::class,
    )->sanitizeVisual($html);

    expect($cleanHtml)
        ->toContain('/media-files/slsc.jpg')
        ->toContain('float:left')
        ->toContain('margin:0 1rem 1rem 0')
        ->toContain('max-width:50%');
});
