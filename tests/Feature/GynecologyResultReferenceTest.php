<?php

use App\Models\Patient;
use App\Models\PathologyRequest;
use App\Models\PathologyResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('gynecology report follows reference and calculates age at result date', function ($category, $expectedCategory) {
    $this->actingAs(User::factory()->create(['role' => 'administrator', 'is_active' => true]));
    $patient = Patient::create(['identity_document' => 'GYN-REF', 'full_name' => 'PACIENTE REFERENCIA', 'birth_date' => '1983-10-28', 'is_active' => true]);
    $this->post(route('solicitudes.store'), ['patient_id' => $patient->id, 'module' => 'G', 'patient_category' => $category, 'request_date' => '2026-04-02', 'sample_quantity' => 1])->assertSessionHasNoErrors();
    $request = PathologyRequest::where('patient_id', $patient->id)->firstOrFail();
    expect($request->patient_category)->toBe($expectedCategory);
    $form = $this->get(route('resultados.create', $request))->assertOk()->assertSee('Dirección / procedencia')->assertSee('Núm. examen patológico')->assertDontSee('<label>Sector</label>', false);
    expect(substr_count($form->getContent(), 'class="diagnosis-line"'))->toBe(2);
    $entries = [
        ['code' => 'G8', 'description' => 'MUESTRA SATISFACTORIA'],
        ['code' => 'I', 'description' => 'NEGATIVO PARA LESION INTRAEPITELIAL O MALIGNIDAD'],
        ['code' => 'J4', 'description' => 'INFLAMACION MODERADA INESPECIFICA'],
        ['code' => 'S', 'description' => 'SE SUGIERE CONTROL EN AÑO'],
    ];
    $this->post(route('resultados.store', $request), ['sample_id' => $request->samples->first()->id, 'expected_revision' => 0, 'result_date' => '2026-04-02', 'reader' => 'ELENA', 'status' => 'validated', 'diagnosis_entries' => $entries])->assertSessionHasNoErrors();
    $result = PathologyResult::where('sample_id', $request->samples->first()->id)->firstOrFail();
    $response = $this->get(route('resultados.imprimir', $result))->assertOk()->assertSee('Informe Paciente de Consulta Externa')->assertSee('GINECOLOGÍA')->assertSee('<dd>42</dd>', false)->assertSee('Jefe de Laboratorio de Patología');
    if ($expectedCategory === 'private') {
        $response->assertSee('<dt>Tipo de paciente</dt>', false)
            ->assertSee('<dd>Particular</dd>', false);
    } else {
        $response->assertDontSee('<dl class="gyn-summary-field patient-category">', false);
    }
    foreach ($entries as $entry) $response->assertSee($entry['code'])->assertSee($entry['description']);
    expect($result->diagnoses()->pluck('code')->all())->toBe(['G8', 'I', 'J4', 'S']);
})->with([['private', 'private'], ['insured', 'insured'], [null, 'insured']]);
