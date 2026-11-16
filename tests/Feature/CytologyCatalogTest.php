<?php

use App\Models\User;
use App\Models\CytologyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('cytology catalogs are persistent and reject duplicates', function (string $type) {
    $this->actingAs(User::factory()->create(['role' => 'administrator']));
    $data = ['type' => $type, 'name' => 'Opción nueva'];
    $this->postJson(route('citologia.catalogos.store'), $data)->assertCreated()->assertJsonPath('option.name', 'OPCION NUEVA');
    $this->postJson(route('citologia.catalogos.store'), $data)->assertUnprocessable();
    $this->get(route('citologia.index'))->assertOk()->assertSee('OPCION NUEVA')->assertDontSee('name="patient_sex"', false);
})->with(['service', 'physician', 'sample', 'care']);

test('a cytology request saves a custom care and sample without sex', function () {
    $this->actingAs(User::factory()->create(['role' => 'administrator']));
    foreach (['care', 'sample'] as $type) {
        $this->postJson(route('citologia.catalogos.store'), ['type' => $type, 'name' => 'NUEVO '.$type])->assertCreated();
    }
    $this->post(route('citologia.store'), [
        'request_date' => '2026-09-24', 'patient_name' => 'PACIENTE PRUEBA',
        'care_type' => 'NUEVO CARE', 'sample_type_code' => 'NUEVO SAMPLE',
        'patient_age' => 32, 'bed_number' => '12', 'registration_number' => '123',
        'clinical_history_number' => '456',
        'hospital_service_id' => \App\Models\HospitalService::query()->create(['name' => 'CLINICA', 'normalized_name' => 'CLINICA', 'is_active' => true])->id,
        'requesting_physician_id' => \App\Models\Physician::query()->create(['full_name' => 'MEDICO PRUEBA', 'is_active' => true])->id,
        'sample_taken_at' => '08:00', 'sample_received_at' => '09:00',
        'clinical_diagnosis' => 'DIAGNOSTICO', 'clinical_data' => 'DATOS CLINICOS',
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('cytology_reports', ['patient_name' => 'PACIENTE PRUEBA', 'care_type' => 'NUEVO CARE', 'sample_type_code' => 'NUEVO SAMPLE', 'patient_sex' => null]);
});

test('only administrators may add cytology catalog options', function () {
    $this->actingAs(User::factory()->create(['role' => 'user']))
        ->postJson(route('citologia.catalogos.store'), ['type' => 'care', 'name' => 'PRUEBA'])->assertForbidden();
});
