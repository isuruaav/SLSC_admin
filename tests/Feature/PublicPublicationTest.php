<?php

use App\Models\Publication;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function publicTestPublication(array $attributes = []): Publication
{
    $key = (string) Str::uuid();
    Storage::disk('local')->put('publications/pdfs/'.$key.'.pdf', "%PDF-1.4\npublication test");
    Storage::disk('local')->put('publications/covers/'.$key.'.jpg', 'cover image bytes');

    return Publication::query()->create([
        'title' => 'Publication '.$key, 'disk' => 'local', 'pdf_path' => 'publications/pdfs/'.$key.'.pdf',
        'cover_path' => 'publications/covers/'.$key.'.jpg', 'cover_width' => 600, 'cover_height' => 800,
        'original_name' => 'journal.pdf', 'size_bytes' => 1024, 'is_published' => true, 'published_at' => now()->subMinute(),
        ...$attributes,
    ]);
}

beforeEach(function (): void {
    Storage::fake('local');
    config()->set('awcms.active_theme', 'slsc');
});

it('shows five newest publications per page with first-page covers linking directly to PDFs', function (): void {
    for ($number = 1; $number <= 6; $number++) {
        publicTestPublication(['title' => 'Journal '.$number, 'published_at' => now()->subMinutes($number)]);
    }
    $response = $this->get('/publications')->assertOk()->assertViewHas('publications', fn ($items) => $items->count() === 5 && $items->total() === 6);
    $response->assertSeeText('Journal 1')->assertDontSeeText('Journal 6')->assertSee('publication-cover', false);
    $first = Publication::query()->where('title', 'Journal 1')->firstOrFail();
    $response->assertSee(route('publications.pdf', ['publication' => $first->uuid]), false)->assertSee(route('publications.cover', ['publication' => $first->uuid]), false);
    $this->get('/publications?page=2')->assertOk()->assertSeeText('Journal 6')->assertDontSeeText('Journal 1');
});

it('hides drafts, future publications and deleted publications from both public files and listing', function (): void {
    $draft = publicTestPublication(['title' => 'Hidden draft', 'is_published' => false, 'published_at' => null]);
    $future = publicTestPublication(['title' => 'Hidden future', 'published_at' => now()->addDay()]);
    $deleted = publicTestPublication(['title' => 'Hidden deleted']);
    $deleted->delete();
    $this->get('/publications')->assertOk()->assertDontSeeText('Hidden draft')->assertDontSeeText('Hidden future')->assertDontSeeText('Hidden deleted');
    foreach ([$draft, $future, $deleted] as $record) {
        $this->get(route('publications.pdf', ['publication' => $record->uuid]))->assertNotFound();
        $this->get(route('publications.cover', ['publication' => $record->uuid]))->assertNotFound();
    }
});

it('serves the PDF inline and the cover as a JPEG with private cache headers', function (): void {
    $record = publicTestPublication(['title' => 'Signals Journal']);
    $this->get(route('publications.pdf', ['publication' => $record->uuid]))->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition', 'inline; filename=signals-journal.pdf')->assertStreamedContent("%PDF-1.4\npublication test");
    $this->get(route('publications.cover', ['publication' => $record->uuid]))->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')->assertStreamedContent('cover image bytes');
});

it('shows separate publication PDFs on their matching language pages without a page switcher', function (): void {
    publicTestPublication(['title' => 'English journal', 'title_si' => 'සංඥා සඟරාව', 'locale' => 'en']);
    publicTestPublication(['title' => 'සිංහල සඟරාව', 'locale' => 'si']);
    publicTestPublication(['title' => 'Fallback journal', 'locale' => 'en']);
    $this->get('/publications')->assertOk()->assertSeeText('English journal')->assertSeeText('Fallback journal')
        ->assertDontSeeText('සිංහල සඟරාව')->assertDontSee('publication-languages', false);
    $this->get('/si/publications')->assertOk()->assertSeeText('ප්‍රකාශන')->assertSeeText('සිංහල සඟරාව')
        ->assertDontSeeText('English journal')->assertDontSeeText('Fallback journal')
        ->assertDontSee('publication-languages', false);
    $this->get('/ta/publications')->assertNotFound();
});

it('requires document permissions to preview a draft in the administration area', function (): void {
    $record = publicTestPublication(['is_published' => false, 'published_at' => null]);
    $url = route('admin.publications.pdf', ['publication' => $record->uuid]);
    $this->get($url)->assertRedirect(route('login'));
    $this->seed(RolesAndPermissionsSeeder::class);
    $auditor = User::factory()->create();
    $auditor->assignRole('Auditor');
    $this->actingAs($auditor)->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
});

it('returns 404 when a publication file has been removed from storage', function (): void {
    $record = publicTestPublication();
    Storage::disk('local')->delete($record->pdf_path);
    $this->get(route('publications.pdf', ['publication' => $record->uuid]))->assertNotFound();
});
