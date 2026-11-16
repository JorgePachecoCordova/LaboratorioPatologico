<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('users sign in with their username and can sign out', function () {
    $user = User::factory()->create();

    $this->get('/')->assertRedirect(route('login'));
    $this->get(route('login'))->assertOk();
    $this->post(route('login.post'), [
        'username' => $user->username,
        'password' => 'password',
    ])->assertRedirect(route('inicio'));
    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('invalid credentials and disabled accounts cannot sign in', function () {
    $user = User::factory()->create();

    $this->post(route('login.post'), [
        'username' => $user->username,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('username');
    $this->assertGuest();

    $user->update(['is_active' => false]);
    $this->post(route('login.post'), [
        'username' => $user->username,
        'password' => 'password',
    ])->assertSessionHasErrors('username');
    $this->assertGuest();
});
