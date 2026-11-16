<?php

use App\Models\{User, CytologyReport};
use App\Support\AccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('anatomopathology reads other module reports without write access', function () {
    $user = User::factory()->create(['role' => 'anatomopathology_office']);
    $this->actingAs($user);
    foreach (['resultados.index', 'resultados.show', 'resultados.imprimir', 'resultados.excel', 'citologia.index', 'citologia.imprimir', 'citologia.excel', 'citologia.reportes.estadistico', 'anatomopatologia.store'] as $route) {
        expect(AccessRoles::allows($user, $route))->toBeTrue();
    }
    foreach (['resultados.create', 'resultados.store', 'resultados.destroy', 'citologia.store', 'citologia.update', 'citologia.informe.update', 'citologia.catalogos.store', 'solicitudes.store', 'muestras.store', 'registros.store'] as $route) {
        expect(AccessRoles::allows($user, $route))->toBeFalse();
    }
    $this->get(route('resultados.index'))->assertOk()->assertDontSee('Registrar resultado');
    $this->postJson(route('citologia.store'), [])->assertForbidden();
    $this->postJson(route('citologia.catalogos.store'), ['type' => 'service', 'name' => 'PRUEBA'])->assertForbidden();
    $report = CytologyReport::query()->create([
        'patient_name' => 'MARIA PEREZ', 'request_number' => 'SC-000001-2026',
        'report_number' => '001-2026', 'status' => 'validated',
        'description' => 'DESCRIPCION GUARDADA', 'conclusion' => 'CONCLUSION GUARDADA',
    ]);
    $snapshot = $report->fresh()->getAttributes();
    $this->get(route('citologia.index', ['registro' => $report->id]))->assertOk()
        ->assertSee('DESCRIPCION GUARDADA')->assertSee('Imprimir informe')
        ->assertDontSee('Nueva solicitud')->assertDontSee('<textarea', false)->assertDontSee('report_action');
    $this->putJson(route('citologia.update', $report), [])->assertForbidden();
    $this->putJson(route('citologia.informe.update', $report), [])->assertForbidden();
    $this->get(route('citologia.index', ['nueva_para' => $report->id]))->assertForbidden();
    $this->get(route('citologia.index', ['nuevo' => 1]))->assertForbidden();
    $draft = CytologyReport::query()->create(['patient_name' => 'BORRADOR PRIVADO', 'request_number' => 'SC-000002-2026', 'status' => 'requested']);
    $this->get(route('citologia.index', ['registro' => $draft->id]))->assertNotFound();
    $this->get(route('citologia.index', ['q' => 'MARIA']))->assertOk()->assertSee('MARIA PEREZ');
    expect($report->fresh()->getAttributes())->toBe($snapshot);
});
