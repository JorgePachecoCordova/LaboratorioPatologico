<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('anatomopathology catalogs create active options and reject duplicates', function (string $type, string $table, string $column) {
    $this->actingAs(User::factory()->create(['role' => 'administrator']));
    $payload = ['type' => $type, 'name' => 'Prueba catálogo'];
    $id = $this->postJson(route('anatomopatologia.catalogos.store'), $payload)
        ->assertCreated()->assertJsonPath('option.name', 'PRUEBA CATALOGO')->json('option.id');
    $this->assertDatabaseHas($table, ['id' => $id, $column => 'PRUEBA CATALOGO', 'is_active' => true]);
    $this->postJson(route('anatomopatologia.catalogos.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->postJson(route('anatomopatologia.catalogos.store'), ['type' => $type, 'name' => ' '])->assertUnprocessable();
})->with([['service', 'hospital_services', 'name'], ['physician', 'physicians', 'full_name']]);

test('anatomopathology catalog creation preserves administrator access restrictions', function () {
    $this->actingAs(User::factory()->create(['role' => 'user']))
        ->postJson(route('anatomopatologia.catalogos.store'), ['type' => 'service', 'name' => 'NO AUTORIZADO'])
        ->assertForbidden();
});

test('anatomopathology office can add services and requesting physicians', function () {
    $this->actingAs(User::factory()->create(['role' => 'anatomopathology_office']));
    $this->get(route('anatomopatologia.index'))->assertOk()
        ->assertSee('id="ap-catalog-dialog"', false)->assertSee('Añadir médico solicitante');
    foreach (['service' => 'SERVICIO NUEVO', 'physician' => 'MARIA PEREZ'] as $type => $name) {
        $payload = ['type' => $type, 'name' => $name];
        $this->postJson(route('anatomopatologia.catalogos.store'), $payload)
            ->assertCreated()->assertJsonPath('option.name', $name);
        $this->postJson(route('anatomopatologia.catalogos.store'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('name');
    }
});
