<?php

use App\Models\ContactMessage;

it('records a valid registration and returns its real reference', function (): void {
    $response = $this->postJson(route('registration.store'), [
        'full_name' => 'Test Visitor', 'email' => 'visitor@example.test', 'phone' => '0771234567',
        'district' => 'Colombo', 'interest' => 'training', 'message' => 'Please send course details.', 'consent' => 'on',
    ])->assertCreated()->assertJsonPath('ok', true);
    $record = ContactMessage::query()->sole();
    expect($record->reference)->toBe($response->json('reference'))
        ->and($record->reference)->toMatch('/^SLSC-\d{6}-[A-F0-9]{12}$/')
        ->and($record->name)->toBe('Test Visitor')->and($record->consented_at)->not->toBeNull();
});

it('rejects registrations without consent using the form error contract', function (): void {
    $this->postJson(route('registration.store'), ['full_name' => 'Visitor'])
        ->assertUnprocessable()->assertJsonPath('ok', false)->assertJsonPath('errors.consent', 'regErrorConsent');
    expect(ContactMessage::query()->count())->toBe(0);
});

it('renders a real endpoint in the public registration form', function (): void {
    config()->set('awcms.active_theme', 'slsc');
    $this->get('/')->assertOk()->assertSee('data-endpoint="'.route('registration.store').'"', false);
});

it('provides a standalone registration form with no duplicate dialog fields', function (): void {
    config()->set('awcms.active_theme', 'slsc');
    foreach ([route('registration.create'), route('registration.create.localized', ['locale' => 'si'])] as $url) {
        $response = $this->get($url)->assertOk()->assertSee('name="submission_id"', false)->assertDontSee('id="registrationDialog"', false);
        expect(substr_count($response->getContent(), 'id="registrationForm"'))->toBe(1);
    }
});

it('redirects an ordinary form submission to a real confirmation reference', function (): void {
    $this->from(route('registration.create'))->post(route('registration.store'), [
        'full_name' => 'Registration Visitor', 'email' => 'registration@example.test', 'phone' => '+94 77 123 4567',
        'district' => 'Colombo', 'interest' => 'communications', 'consent' => '1',
    ])->assertRedirect(route('registration.create'))->assertSessionHas('registration-reference');
    $reference = ContactMessage::query()->sole()->reference;
    $this->get(route('registration.create'))->assertOk()->assertSeeText($reference);
});

it('validates telephone numbers and preserves form input after errors', function (): void {
    $this->from(route('registration.create'))->post(route('registration.store'), [
        'full_name' => 'Registration Visitor', 'email' => 'registration@example.test', 'phone' => 'invalid phone',
        'district' => 'Colombo', 'interest' => 'general', 'consent' => '1',
    ])->assertSessionHasErrors('phone');
    $this->get(route('registration.create'))->assertOk()->assertSee('value="Registration Visitor"', false);
    expect(ContactMessage::query()->count())->toBe(0);
});
