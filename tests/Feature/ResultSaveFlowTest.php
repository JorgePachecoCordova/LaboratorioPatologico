<?php

use App\Models\Patient;
use App\Models\PathologyRequest;
use App\Models\PathologyResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('saving keeps the selected sample open with its print link', function () {
    $this->actingAs(User::factory()->create());
    $patient = Patient::create(['identity_document' => 'SAVE-TEST', 'full_name' => 'PACIENTE PRUEBA', 'is_active' => true]);
    $this->post(route('solicitudes.store'), [
        'patient_id' => $patient->id, 'module' => 'R', 'request_date' => '2026-10-03',
        'sample_collection_date' => '2026-10-03', 'sample_quantity' => 2,
    ])->assertSessionHasNoErrors();
    $request = PathologyRequest::firstOrFail();
    $sample = $request->samples()->orderByDesc('sample_number')->firstOrFail();
    foreach (['PRIMER DIAGNOSTICO', 'DIAGNOSTICO MODIFICADO'] as $diagnosis) {
        $response = $this->post(route('resultados.store', $request), [
            'notes' => $diagnosis, 'sample_id' => $sample->id, 'result_date' => '2026-10-03', 'status' => 'validated',
            'diagnosis_entries' => [['code' => 'PRUEBA', 'description' => $diagnosis]],
        ])->assertSessionHasNoErrors();
        $response->assertRedirect(route('resultados.create', ['solicitud' => $request, 'sample_id' => $sample->id]));
        $result = PathologyResult::where('sample_id', $sample->id)->firstOrFail();
        $this->followRedirects($response)->assertOk()->assertSee($diagnosis)
            ->assertSee(route('resultados.imprimir', $result), false);
        $this->assertDatabaseCount('pathology_results', 1);
    }
});

