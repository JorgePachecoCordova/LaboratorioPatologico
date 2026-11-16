<?php

use App\Models\Patient;
use App\Models\PathologyRequest;
use App\Models\PathologyResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('rural and urban results follow their own reference', function (string $module) {
    $this->actingAs(User::factory()->create(['role' => 'administrator', 'is_active' => true]));
    $patient = Patient::create(['identity_document' => 'CI-REFERENCE-'.$module, 'full_name' => 'PACIENTE REFERENCIA', 'birth_date' => '1983-10-28', 'is_active' => true]);
    $this->post(route('solicitudes.store'), ['patient_id' => $patient->id, 'module' => $module, 'request_date' => '2026-04-02', 'sample_quantity' => 1])->assertSessionHasNoErrors();
    $request = PathologyRequest::where('patient_id', $patient->id)->firstOrFail();
    $form = $this->get(route('resultados.create', $request))->assertOk();
    expect(substr_count($form->getContent(), 'class="diagnosis-line"'))->toBe(2);
    $payload = ['sample_id' => $request->samples->first()->id, 'expected_revision' => 0, 'result_date' => '2026-04-02', 'reader' => 'ELENA', 'status' => 'validated', 'diagnosis_entries' => [['code' => 'G8', 'description' => 'MUESTRA SATISFACTORIA'], ['code' => 'S', 'description' => 'SE SUGIERE CONTROL EN AÑO']]];
    if ($module === 'R') $payload += ['visit_number' => 2, 'insurance_number' => 'SEGURO-123'];
    $this->post(route('resultados.store', $request), $payload)->assertSessionHasNoErrors();
    $result = PathologyResult::where('sample_id', $payload['sample_id'])->firstOrFail();
    $response = $this->get(route('resultados.imprimir', $result))->assertOk()->assertSee('MUESTRA SATISFACTORIA')->assertSee('SE SUGIERE CONTROL EN AÑO');
    if ($module === 'R') {
        $response->assertSee('Secretaría regional')->assertSee('Establecimiento')->assertSee('SEGURO-123')->assertSee('<td>2</td>', false)->assertSee('<td>42</td>', false)->assertDontSee('CI-REFERENCE-R')->assertDontSee('Fecha resultado');
        expect($result->insurance_number)->toBe('SEGURO-123');
        $this->post(route('resultados.store', $request), array_replace($payload, ['expected_revision' => $result->revision, 'visit_number' => null, 'insurance_number' => null]))->assertSessionHasNoErrors();
        expect($result->fresh()->insurance_number)->toBeNull();
    } else {
        $response->assertSee('Fecha resultado')->assertSee('Sector')->assertSee('Distrito')->assertSee('Área')->assertDontSee('Núm. asegurado')->assertSee('Nombre y firma del responsable');
    }
})->with(['R', 'U']);