<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('public registration is disabled', function (): void {
    $this->get('/register')
        ->assertNotFound();
});

test('guests are redirected from the admin dashboard', function (): void {
    $this->get('/admin')
        ->assertRedirect('/login');
});

test('users without admin permission are forbidden', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('super administrators can access the admin dashboard', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->assignRole('Super Administrator');

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSee('AWCMS Dashboard')
        ->assertSee('Content to Review');
});
