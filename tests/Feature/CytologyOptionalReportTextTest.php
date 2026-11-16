<?php

use App\Models\CytologyReport;
use App\Models\HospitalService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function optionalCytologyRequestData(): array
{
    return [
        'request_date' => '2026-11-10',
        'patient_name' => 'PACIENTE PRUEBA',
        'patient_age' => 35,
        'hospital_service_id' => HospitalService::create([
            'name' => 'CLINICA', 'normalized_name' => 'CLINICA', 'is_active' => true,
        ])->id,
        'sample_type_code' => 'LIQUIDO PLEURAL',
    ];
}

test('cytology can be finalized and exported with optional report text', function (array $text) {
    $this->actingAs(User::factory()->create(['role' => 'cytology_office']));
    $this->post(route('citologia.store'), optionalCytologyRequestData() + [
        'report_action' => 'validate',
    ] + $text)->assertSessionHasNoErrors()->assertRedirect();

    $report = CytologyReport::firstOrFail();
    expect($report->status)->toBe('validated');
    expect($report->report_number)->not->toBeNull();
    $this->get(route('citologia.imprimir', $report))->assertOk();
    $response = $this->get(route('citologia.excel', $report))->assertOk();
    $response->assertDownload();
    if ($response->baseResponse instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
        @unlink($response->baseResponse->getFile()->getPathname());
    }
})->with([
    'omitted' => [[]],
    'blank' => [['description' => '  ', 'conclusion' => '']],
    'description only' => [['description' => 'DESCRIPCION']],
    'conclusion only' => [['conclusion' => 'CONCLUSION']],
]);

test('an existing cytology request can be finalized without description or conclusion', function () {
    $this->actingAs(User::factory()->create(['role' => 'cytology_office']));
    $this->post(route('citologia.store'), optionalCytologyRequestData())
        ->assertSessionHasNoErrors();
    $report = CytologyReport::firstOrFail();

    $this->put(route('citologia.informe.update', $report), [
        'report_action' => 'validate', 'description' => '', 'conclusion' => '',
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect($report->fresh()->status)->toBe('validated');
    $this->get(route('citologia.imprimir', $report))->assertOk();
});
