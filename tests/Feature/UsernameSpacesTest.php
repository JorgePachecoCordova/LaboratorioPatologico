<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('a username with spaces can be created edited and used to sign in', function () {
    $administrator = User::factory()->create(['role' => 'administrator']);
    $this->actingAs($administrator)->post(route('configuracion.usuarios.store'), [
        'name' => 'MARINA GUTIERREZ',
        'username' => 'MARINA GUTIERREZ',
        'password' => 'Marina_2026',
        'password_confirmation' => 'Marina_2026',
    ])->assertSessionHasNoErrors();

    $user = User::where('username', 'MARINA GUTIERREZ')->firstOrFail();
    $this->put(route('configuracion.usuarios.update', $user), [
        'name' => $user->name,
        'username' => 'MARINA GUTIÉRREZ',
        'role' => $user->role,
    ])->assertSessionHasNoErrors();

    $this->post(route('logout'));
    $this->post(route('login.post'), [
        'username' => 'MARINA GUTIÉRREZ',
        'password' => 'Marina_2026',
    ])->assertRedirect(route('inicio'));
    $this->assertAuthenticatedAs($user);
});
