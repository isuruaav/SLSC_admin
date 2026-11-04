<?php

beforeEach(function (): void {
    // Exercise the real CSRF middleware; the database and storage remain isolated.
    app()->instance('env', 'local');
});

afterEach(function (): void {
    app()->instance('env', 'testing');
});

it('rejects a login request without a session CSRF token', function (): void {
    $this->post('/login', ['email' => 'test@example.test', 'password' => 'incorrect'])
        ->assertStatus(419);
    $this->assertGuest();
});

it('requires a valid session CSRF token on the custom Livewire update route', function (): void {
    $uri = app('livewire')->getUpdateUri();
    $this->withHeaders(['X-Livewire' => '1'])->post($uri, ['components' => []])->assertStatus(419);

    $this->withSession(['_token' => 'verification-token'])
        ->withHeaders(['X-Livewire' => '1', 'X-CSRF-TOKEN' => 'verification-token'])
        ->post($uri, ['components' => []])->assertNotFound();
});
