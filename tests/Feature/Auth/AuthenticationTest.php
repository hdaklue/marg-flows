<?php

use App\Models\User;
use Filament\Pages\Auth\Login;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'), // make sure password matches
    ]);

    Livewire::test(Login::class)
        ->set('data', [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/');

    $this->assertAuthenticated();
});
test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'), // make sure password matches
    ]);

    $response = Livewire::test(Login::class)
        ->set('data', [
            'email' => $user->email,
            'password' => 'wrong-password',

        ])
        ->call('authenticate');

    $response->assertHasErrors('data.email');

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/login');

    $this->assertGuest();
});
