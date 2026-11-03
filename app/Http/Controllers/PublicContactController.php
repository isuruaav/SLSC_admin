<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Services\ContentSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PublicContactController
{
    public function create(Request $request): View
    {
        $locale = $this->setLocale($request);

        return view('public.contact.index', [
            'settings' => SiteSetting::current(),
            'languageVersions' => array_map(static fn (string $code): array => [
                'code' => $code,
                'available' => true,
                'active' => $code === $locale,
                'url' => $code === 'en' ? route('contact.create') : route('contact.create.localized', ['locale' => $code]),
            ], ['en', 'si']),
        ]);
    }

    public function store(Request $request, ContentSanitizer $sanitizer): RedirectResponse
    {
        $this->setLocale($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:10000'],
            'website' => ['nullable', 'max:0'],
        ]);

        ContactMessage::query()->create([
            'name' => $sanitizer->plainText((string) $validated['name'], 150),
            'email' => mb_strtolower(trim((string) $validated['email'])),
            'phone' => isset($validated['phone']) ? $sanitizer->plainText((string) $validated['phone'], 50) : null,
            'subject' => $sanitizer->plainText((string) $validated['subject'], 200),
            'message' => $sanitizer->plainText((string) $validated['message'], 10000),
            'status' => 'new',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', __('public.contact_page.received'));
    }

    private function setLocale(Request $request): string
    {
        $locale = $request->route('locale', 'en');
        $locale = is_string($locale) && in_array($locale, ['en', 'si'], true) ? $locale : 'en';
        app()->setLocale($locale);

        return $locale;
    }
}
