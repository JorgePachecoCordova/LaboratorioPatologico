<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('an administrator can create a user with a four character password', function () {
    $administrator = User::factory()->create(['role' => 'administrator']);

    $response = $this->actingAs($administrator)->post(route('configuracion.usuarios.store'), [
        'name' => 'Usuario LPAT',
        'username' => 'usuario_lpat',
        'password' => 'LPAT',
        'password_confirmation' => 'LPAT',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('users', ['username' => 'usuario_lpat']);
});

test('a password can combine letters numbers and symbols', function () {
    $administrator = User::factory()->create(['role' => 'administrator']);

    $response = $this->actingAs($administrator)->post(route('configuracion.usuarios.store'), [
        'name' => 'Usuario Mixto',
        'username' => 'usuario_mixto',
        'password' => 'L1@T',
        'password_confirmation' => 'L1@T',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('users', ['username' => 'usuario_mixto']);
});

test('a created user stores optional contact information and keeps the common role', function () {
    $administrator = User::factory()->create(['role' => 'administrator']);

    $response = $this->actingAs($administrator)->post(route('configuracion.usuarios.store'), [
        'name' => 'Usuario General',
        'username' => 'usuario_general',
        'email' => 'usuario@example.com',
        'phone' => '72400000',
        'role' => 'administrator',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('users', [
        'username' => 'usuario_general',
        'email' => 'usuario@example.com',
        'phone' => '72400000',
        'role' => 'reception',
    ]);
});

test('editing can update the email and role', function () {
    $administrator = User::factory()->create(['role' => 'administrator']);
    $user = User::factory()->create();

    $response = $this->actingAs($administrator)->put(
        route('configuracion.usuarios.update', $user),
        [
            'name' => 'Usuario Actualizado',
            'username' => $user->username,
            'role' => 'technician',
            'email' => 'actualizado@example.com',
        ],
    );

    $response->assertSessionHasNoErrors();
    expect($user->fresh())
        ->role->toBe('technician')
        ->email->toBe('actualizado@example.com');
});
