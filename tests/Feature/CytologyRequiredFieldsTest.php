<?php

use App\Models\CytologyReport;
use App\Models\HospitalService;
use App\Models\Physician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('cytology requires complete request data before saving or updating', function () {
    $this->actingAs(User::factory()->create());
    $data = [
        'request_date' => '2026-10-14', 'patient_name' => 'MARIA PEREZ',
        'patient_age' => 0, 'care_type' => 'hospitalized', 'bed_number' => '12',
        'registration_number' => '123', 'clinical_history_number' => '456',
        'hospital_service_id' => HospitalService::query()->create(['name' => 'CLINICA', 'normalized_name' => 'CLINICA', 'is_active' => true])->id,
        'requesting_physician_id' => Physician::query()->create(['full_name' => 'MEDICO PRUEBA', 'is_active' => true])->id,
        'sample_type_code' => 'OTROS', 'sample_type_other' => 'MUESTRA ESPECIAL',
        'sample_taken_at' => '08:00', 'sample_received_at' => '09:00',
        'clinical_diagnosis' => 'DIAGNOSTICO', 'clinical_data' => 'DATOS CLINICOS',
    ];
    foreach (array_keys($data) as $field) {
        $incomplete = $data;
        unset($incomplete[$field]);
        $this->postJson(route('citologia.store'), $incomplete)
            ->assertUnprocessable()->assertJsonValidationErrors($field);
    }
    $this->assertDatabaseCount('cytology_reports', 0);
    $this->post(route('citologia.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
    $report = CytologyReport::query()->firstOrFail();
    $this->putJson(route('citologia.update', $report), ['patient_name' => 'INCOMPLETO'])
        ->assertUnprocessable()->assertJsonValidationErrors(['patient_age', 'clinical_data', 'hospital_service_id']);
    expect($report->fresh()->patient_name)->toBe('MARIA PEREZ');
});
