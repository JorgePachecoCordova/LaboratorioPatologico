<?php

use App\Models\Patient;
use App\Models\PathologyRequest;
use App\Models\PathologyResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('daily register separates areas and dates and includes requests without results', function () {
    $this->actingAs(User::factory()->create());
    foreach (['R', 'U', 'G'] as $module) {
        $patient = Patient::query()->create(['identity_document' => 'TEST-'.$module, 'full_name' => 'PACIENTE PRUEBA '.$module, 'birth_date' => '2000-10-01', 'is_active' => true]);
        $this->post(route('solicitudes.store'), [
            'patient_id' => $patient->id, 'module' => $module, 'request_date' => '2025-09-15',
            'sample_collection_date' => '2025-09-15', 'sample_quantity' => 2,
        ])->assertRedirect();
    }
    foreach (['R', 'U', 'G'] as $module) {
        $response = $this->get(route('solicitudes.registro-diario', ['module' => $module, 'date' => '2025-09-15']));
        $response->assertOk()->assertSee('PACIENTE PRUEBA '.$module)->assertSee('PENDIENTE')
            ->assertSee('Total de solicitudes: 1')->assertSee('Imprimir / Guardar PDF')
            ->assertSee('>24</td>', false);
        foreach (array_diff(['R', 'U', 'G'], [$module]) as $other) $response->assertDontSee('PACIENTE PRUEBA '.$other);
    }
    $this->get(route('solicitudes.registro-diario', ['module' => 'R', 'date' => '2025-09-16']))
        ->assertOk()->assertSee('No hay solicitudes')->assertDontSee('PACIENTE PRUEBA R');

    $record = PathologyRequest::where('module', 'R')->firstOrFail();
    $record->update(['notes' => 'OBSERVACION DE SOLICITUD']);
    $result = PathologyResult::create([
        'sample_id' => $record->samples->first()->id, 'result_date' => '2025-09-17',
        'diagnosis' => 'RESULTADO DE PRUEBA', 'status' => 'validated', 'notes' => 'COMENTARIO DEL RESULTADO',
    ]);
    $result->diagnoses()->create(['position' => 1, 'code' => 'NILM', 'description' => 'RESULTADO DE PRUEBA']);
    $this->get(route('solicitudes.registro-diario', ['module' => 'R', 'date' => '2025-09-15']))
        ->assertOk()->assertSee('NILM')->assertDontSee('RESULTADO DE PRUEBA')->assertSee('PENDIENTE')
        ->assertDontSee('Muestra 1:')->assertDontSee('Muestra 2:')
        ->assertSee('PUESTO DE SALUD</th>', false)->assertSee('N.º DE ESTADO')->assertSee('RESULTADOS</th>', false)
        ->assertSee('OBSERVACIONES')->assertSee('OBSERVACION DE SOLICITUD')->assertSee('COMENTARIO DEL RESULTADO');
    $record->delete();
    $this->get(route('solicitudes.registro-diario', ['module' => 'R', 'date' => '2025-09-15']))
        ->assertOk()->assertDontSee('PACIENTE PRUEBA R');
});

test('daily register validates filters and requires authentication', function () {
    $this->get(route('solicitudes.registro-diario'))->assertRedirect();
    $this->actingAs(User::factory()->create());
    $this->getJson(route('solicitudes.registro-diario', ['date' => '2024-12-31', 'module' => 'X']))
        ->assertUnprocessable()->assertJsonValidationErrors(['date', 'module']);
    $this->getJson(route('solicitudes.registro-diario', ['date' => '2025-02-30']))
        ->assertUnprocessable()->assertJsonValidationErrors('date');
    $this->get(route('solicitudes.index'))->assertOk()->assertSee('Registro diario');
});
