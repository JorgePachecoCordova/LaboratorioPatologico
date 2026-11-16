<?php

use App\Models\Patient;
use App\Models\PathologyRequest;
use App\Models\PathologyResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('reception and readers can edit another authors result without a correction reason', function (string $role) {
    $author = User::factory()->create(['role' => 'result_reader', 'is_active' => true]);
    $editor = User::factory()->create(['role' => $role, 'is_active' => true]);
    $this->actingAs(User::factory()->create(['role' => 'administrator', 'is_active' => true]));
    $patient = Patient::create(['identity_document' => 'SHARED-'.$role, 'full_name' => 'PACIENTE PRUEBA', 'is_active' => true]);
    $this->post(route('solicitudes.store'), [
        'patient_id' => $patient->id, 'module' => 'R', 'request_date' => '2026-10-27',
        'sample_collection_date' => '2026-10-27', 'sample_quantity' => 1,
    ])->assertSessionHasNoErrors();
    $request = PathologyRequest::where('patient_id', $patient->id)->firstOrFail();
    $result = PathologyResult::create([
        'sample_id' => $request->samples->first()->id, 'recorded_by' => $author->id,
        'result_date' => '2026-10-27', 'diagnosis' => 'ANTERIOR', 'status' => 'validated',
    ]);
    $this->actingAs($editor);
    $this->get(route('resultados.create', $request))->assertOk()->assertDontSee('Motivo de la corrección');
    $this->post(route('resultados.store', $request), [
        'sample_id' => $result->sample_id, 'expected_revision' => $result->revision ?? 0,
        'result_date' => '2026-10-27', 'reader' => 'ELENA', 'status' => 'validated',
        'diagnosis_entries' => [['code' => 'NILM', 'description' => 'ACTUALIZADO']],
    ])->assertSessionHasNoErrors();
    expect($result->fresh()->diagnosis)->toContain('ACTUALIZADO');
    expect($result->fresh()->recorded_by)->toBe($author->id);
    expect($result->audits()->latest('id')->first()->actor_id)->toBe($editor->id);
})->with(['reception', 'result_reader']);