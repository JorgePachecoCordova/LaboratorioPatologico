<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('expired AJAX sessions return JSON instead of login HTML', function () {
    $this->getJson(route('pacientes.buscar', ['q' => 'PACIENTE']))
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Su sesión ha finalizado. Inicie sesión nuevamente.');

    $this->get(route('inicio'))->assertRedirect(route('login'));
});

test('disabled accounts are logged out for AJAX and normal navigation', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)->getJson(route('pacientes.buscar'))
        ->assertUnauthorized()
        ->assertJsonPath('message', 'La cuenta se encuentra deshabilitada.');
    $this->assertGuest();

    $this->actingAs($user)->get(route('inicio'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('username');
    $this->assertGuest();
});
