<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\ContentSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class PublicRegistrationController
{
    public function create(Request $request): View
    {
        $locale = $request->route('locale') === 'si' ? 'si' : 'en';
        app()->setLocale($locale);

        return view('theme-slsc::registration', [
            'standaloneRegistration' => true,
            'currentLocale' => $locale,
            'languageVersions' => [
                ['code' => 'en', 'available' => true, 'url' => route('registration.create')],
                ['code' => 'si', 'available' => true, 'url' => route('registration.create.localized', ['locale' => 'si'])],
            ],
        ]);
    }

    public function __invoke(Request $request, ContentSanitizer $sanitizer): JsonResponse|RedirectResponse
    {
        $locale = $request->route('locale') === 'si' ? 'si' : 'en';
        app()->setLocale($locale);
        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! preg_match('/^\+?[0-9]{7,15}$/', preg_replace('/[\s().-]/', '', $value) ?? '')) {
                    $fail('Enter a valid telephone number.');
                }
            }],
            'district' => ['required', 'string', 'min:2', 'max:80'],
            'interest' => ['required', 'in:communications,technology,training,general'],
            'message' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'max:0'],
        ]);
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                $codes = ['full_name' => 'regErrorName', 'email' => 'regErrorEmail', 'phone' => 'regErrorPhone', 'district' => 'regErrorDistrict', 'interest' => 'regErrorInterest', 'message' => 'regErrorMessage', 'consent' => 'regErrorConsent'];

                return response()->json(['ok' => false, 'code' => 'regFixErrors', 'errors' => array_intersect_key($codes, $validator->errors()->messages())], 422);
            }

            return back()->withErrors($validator)->withInput();
        }
        $data = $validator->validated();
        $reference = 'SLSC-'.now('Asia/Colombo')->format('ymd').'-'.strtoupper(bin2hex(random_bytes(6)));
        ContactMessage::query()->create([
            'name' => $sanitizer->plainText($data['full_name'], 120),
            'email' => mb_strtolower(trim($data['email'])),
            'phone' => $sanitizer->plainText($data['phone'], 30),
            'district' => $sanitizer->plainText($data['district'], 80),
            'interest' => $data['interest'],
            'subject' => 'Registration: '.ucfirst($data['interest']),
            'message' => $sanitizer->plainText($data['message'] ?? '', 2000),
            'reference' => $reference, 'consented_at' => now(), 'status' => 'new', 'ip_address' => $request->ip(),
        ]);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'reference' => $reference], 201)
            : back()->with('status', __('public.contact_page.received').' '.$reference)->with('registration-reference', $reference);
    }
}
