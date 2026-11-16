<?php

use App\Models\CytologyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('report exports reject years before 2025', function () {
    $this->actingAs(User::factory()->create());
    foreach (['citologia.reportes.estadistico', 'resultados.positivos.mensual'] as $route) {
        foreach (['excel', 'print'] as $format) {
            $this->getJson(route($route, ['year' => 2024, 'month' => 9, 'format' => $format]))
                ->assertUnprocessable()->assertJsonValidationErrors('year');
        }
    }
});

test('all cytology reports can be viewed and printed starting in 2025', function () {
    $this->actingAs(User::factory()->create());
    foreach (['urban', 'rural', 'age'] as $type) {
        $this->get(route('citologia.reportes.estadistico', ['year' => 2025, 'month' => 9, 'type' => $type, 'format' => 'print']))
            ->assertOk()->assertSee('Imprimir / Guardar PDF')->assertSee('2025');
    }
    $this->get(route('resultados.positivos.mensual', ['year' => 2025, 'month' => 9, 'format' => 'print']))
        ->assertOk()->assertSee('Imprimir / Guardar PDF')->assertSee('No hay registros');
});

test('cytology opens with a blank form even when requests exist', function () {
    $user = User::factory()->create();
    CytologyReport::query()->create([
        'request_number' => 'SC-000001-2026',
        'request_date' => '2026-08-25',
        'patient_name' => 'PACIENTE ANTERIOR',
        'sample_type' => 'LIQUIDO ASCITICO',
        'sample_type_code' => 'LIQUIDO ASCITICO',
        'status' => 'requested',
    ]);

    $this->actingAs($user)
        ->get(route('citologia.index'))
        ->assertOk()
        ->assertSee('NUMERACIÓN AUTOMÁTICA')
        ->assertDontSee('Resultado e informe')
        ->assertDontSee('Solicitud y muestra')
        ->assertDontSee('PACIENTE ANTERIOR');
});

test('cytology reports are available inside the cytology module', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('citologia.index'))
        ->assertOk()
        ->assertSee('Reportes y consolidaciones')
        ->assertSee('ÁREA URBANA')
        ->assertSee('Redes y municipios')
        ->assertSee('GRUPOS ETARIOS')
        ->assertSee('Casos positivos');
});

test('an existing cytology request appears when explicitly selected', function () {
    $user = User::factory()->create();
    $report = CytologyReport::query()->create([
        'request_number' => 'SC-000001-2026',
        'request_date' => '2026-08-25',
        'patient_name' => 'PACIENTE SELECCIONADO',
        'sample_type' => 'LIQUIDO ASCITICO',
        'sample_type_code' => 'LIQUIDO ASCITICO',
        'status' => 'requested',
    ]);

    $this->actingAs($user)
        ->get(route('citologia.index', ['registro' => $report->id]))
        ->assertOk()
        ->assertSee('PACIENTE SELECCIONADO')
        ->assertSee('INFORME CITOLÓGICO')
        ->assertDontSee('SOLICITUD DE EXAMEN CITOLÓGICO');
});

test('searching an existing cytology record opens its report instead of its request', function () {
    $user = User::factory()->create();
    CytologyReport::query()->create([
        'request_number' => 'SC-000003-2026',
        'request_date' => '2026-09-01',
        'patient_name' => 'ARIEL TOLAY',
        'sample_type' => 'LIQUIDO PLEURAL',
        'sample_type_code' => 'LIQUIDO PLEURAL',
        'status' => 'requested',
    ]);

    $this->actingAs($user)
        ->get(route('citologia.index', ['q' => 'ARIEL TOLAY']))
        ->assertOk()
        ->assertSee('INFORME CITOLÓGICO')
        ->assertDontSee('SOLICITUD DE EXAMEN CITOLÓGICO');
});

test('the report view does not repeat the request step in its header', function () {
    $user = User::factory()->create();
    $report = CytologyReport::query()->create([
        'request_number' => 'SC-000002-2026',
        'request_date' => '2026-09-09',
        'patient_name' => 'PACIENTE INFORME',
        'sample_type' => 'LIQUIDO PLEURAL',
        'sample_type_code' => 'LIQUIDO PLEURAL',
        'status' => 'requested',
    ]);

    $this->actingAs($user)
        ->get(route('citologia.index', ['registro' => $report->id, 'tab' => 'informe']))
        ->assertOk()
        ->assertSee('Resultado e informe')
        ->assertDontSee('Solicitud y muestra');
});

test('a saved cytology request can be printed before its report is completed', function () {
    $user = User::factory()->create();
    $report = CytologyReport::query()->create([
        'request_number' => 'SC-000006-2026',
        'report_number' => null,
        'request_date' => '2026-09-01',
        'patient_name' => 'PACIENTE PARA SOLICITUD',
        'sample_type' => 'LIQUIDO ASCITICO',
        'sample_type_code' => 'LIQUIDO ASCITICO',
        'status' => 'requested',
    ]);

    $this->actingAs($user)
        ->get(route('citologia.solicitud.imprimir', $report))
        ->assertOk()
        ->assertSee('SOLICITUD DE EXAMEN CITOLÓGICO')
        ->assertSee('SC-000006-2026');
});

test('a cytology result can be downloaded as a real excel workbook', function () {
    $user = User::factory()->create();
    $report = CytologyReport::query()->create([
        'request_number' => 'SC-000007-2026',
        'report_number' => '007-2026',
        'request_date' => '2026-09-01',
        'report_date' => '2026-09-10',
        'patient_name' => 'PACIENTE EXCEL',
        'sample_type' => 'LIQUIDO PLEURAL',
        'sample_type_code' => 'LIQUIDO PLEURAL',
        'description' => 'DESCRIPCION COMPLETA',
        'conclusion' => 'CONCLUSION COMPLETA',
        'status' => 'validated',
    ]);

    $response = $this->actingAs($user)->get(route('citologia.excel', $report));

    $response->assertOk()
        ->assertDownload('Informe-citologico-007-2026.xlsx')
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $path = $response->baseResponse->getFile()->getPathname();
    $zip = new ZipArchive();
    expect($zip->open($path))->toBeTrue();
    expect($zip->getFromName('xl/worksheets/sheet1.xml'))
        ->toContain('PACIENTE EXCEL')
        ->toContain('DESCRIPCION COMPLETA')
        ->toContain('CONCLUSION COMPLETA');
    $zip->close();
});
