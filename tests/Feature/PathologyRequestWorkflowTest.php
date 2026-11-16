<?php

use App\Models\Patient;
use App\Models\PathologyRequest;
use App\Models\PathologyResult;
use App\Models\Sample;
use App\Models\User;
use App\Services\RequestNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('each request series restarts at one when the year changes', function () {
    $service = app(RequestNumberService::class);

    $first2026 = $service->next('U', 2026);
    $second2026 = $service->next('U', 2026);
    $first2027 = $service->next('U', 2027);
    $firstRural2027 = $service->next('R', 2027);

    expect($first2026['sequence_number'])->toBe(1)
        ->and($second2026['sequence_number'])->toBe(2)
        ->and($first2027['sequence_number'])->toBe(1)
        ->and($firstRural2027['sequence_number'])->toBe(1);
});

test('saving a request creates its samples and sends it to results', function () {
    $user = User::factory()->create();
    $patient = Patient::query()->create([
        'identity_document' => '8520716',
        'full_name' => 'JORGE PACHECO CORDOVA',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('solicitudes.store'), [
        'patient_id' => $patient->id,
        'module' => 'U',
        'request_date' => '2027-01-05',
        'sample_collection_date' => '2027-01-05',
        'sample_quantity' => 2,
    ]);

    $response->assertRedirect(route('solicitudes.index'));

    $request = PathologyRequest::query()->firstOrFail();
    expect($request->sequence_year)->toBe(2027)
        ->and($request->sequence_number)->toBe(1)
        ->and($request->request_type)->toBe('CITOLOGIA')
        ->and($request->samples()->count())->toBe(2);

    $this->actingAs($user)->get(route('resultados.index'))
        ->assertOk()
        ->assertSee($request->request_number)
        ->assertSee('JORGE PACHECO CORDOVA');

    $resultForm = $this->actingAs($user)->get(route('resultados.create', $request));
    $resultForm->assertOk();
    expect(substr_count($resultForm->getContent(), 'Formulario de resultados'))->toBe(1);

    $this->actingAs($user)->get(route('solicitudes.show', $request))
        ->assertOk()
        ->assertSee('Datos del paciente')
        ->assertSee('Datos de la solicitud')
        ->assertSee('JORGE PACHECO CORDOVA');
});

test('patient api finds partial names and terms in any order', function () {
    $user = User::factory()->create();
    Patient::query()->create([
        'identity_document' => '8520716',
        'full_name' => 'JORGE PACHECO CORDOVA',
        'is_active' => true,
    ]);

    $this->actingAs($user)->getJson(route('pacientes.buscar', ['q' => 'JORGE']))
        ->assertOk()->assertJsonPath('patients.0.full_name', 'JORGE PACHECO CORDOVA');

    $this->actingAs($user)->getJson(route('pacientes.buscar', ['q' => 'PACHECO JORGE']))
        ->assertOk()->assertJsonPath('patients.0.identity_document', '8520716');
});

test('multiple diagnostic codes are stored and printed with the result', function () {
    $user = User::factory()->create();
    $patient = Patient::query()->create([
        'identity_document' => '7001001', 'full_name' => 'PACIENTE RESULTADO', 'is_active' => true,
    ]);

    $this->actingAs($user)->post(route('solicitudes.store'), [
        'patient_id' => $patient->id, 'module' => 'G', 'request_date' => '2027-02-10',
        'sample_collection_date' => '2027-02-10', 'sample_quantity' => 1,
    ])->assertRedirect();

    $pathologyRequest = PathologyRequest::query()->with('samples')->firstOrFail();
    $this->actingAs($user)->post(route('resultados.store', $pathologyRequest), [
        'sample_id' => $pathologyRequest->samples->first()->id,
        'result_date' => '2027-02-11', 'status' => 'validated',
        'is_positive' => 1,
        'sample_adequacy' => 'satisfactory', 'general_category' => 'negative',
        'diagnosis' => 'CONCLUSION GENERAL',
        'notes' => 'Comentario clínico importante',
        'diagnosis_entries' => [
            ['code' => 'G8', 'description' => 'MUESTRA SATISFACTORIA'],
            ['code' => 'I', 'description' => 'NEGATIVO PARA LESION INTRAEPITELIAL'],
        ],
    ])->assertRedirect(route('resultados.index'));

    $result = $pathologyRequest->samples->first()->pathologyResult()->firstOrFail();
    expect($result->diagnoses()->count())->toBe(2);

    $this->actingAs($user)->get(route('resultados.create', $pathologyRequest))
        ->assertOk()->assertSee('COMENTARIO CLÍNICO IMPORTANTE');
    $this->actingAs($user)->get(route('resultados.create', [
        'solicitud' => $pathologyRequest, 'nuevo' => 1,
        'sample_id' => $result->sample_id,
    ]))->assertOk()->assertSee('Volver al resultado anterior')
        ->assertDontSee('COMENTARIO CLÍNICO IMPORTANTE');
    expect($result->fresh()->notes)->toBe('COMENTARIO CLÍNICO IMPORTANTE');

    $this->actingAs($user)->get(route('resultados.imprimir', $result))
        ->assertOk()->assertSee('G8')->assertSee('MUESTRA SATISFACTORIA')
        ->assertSee('NEGATIVO PARA LESION INTRAEPITELIAL')
        ->assertSee('Comentario')
        ->assertSee('COMENTARIO CLÍNICO IMPORTANTE');

    $indexResponse = $this->actingAs($user)->get(route('resultados.index'));
    $indexResponse->assertOk()->assertSee(route('resultados.excel', $result), false);

    $excelResponse = $this->actingAs($user)->get(route('resultados.excel', $result));
    $excelResponse->assertOk()
        ->assertDownload('Resultado-'.$pathologyRequest->request_number.'.xlsx')
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $excelPath = $excelResponse->baseResponse->getFile()->getPathname();
    $zip = new ZipArchive();
    expect($zip->open($excelPath))->toBeTrue();
    expect($zip->getFromName('xl/worksheets/sheet1.xml'))
        ->toContain('PACIENTE RESULTADO')
        ->toContain('NEGATIVO PARA LESION INTRAEPITELIAL')
        ->toContain('COMENTARIO CLÍNICO IMPORTANTE');
    $sheet = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
    $sheet->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $diagnosticRows = $sheet->xpath('//s:row[s:c/s:is/s:t[starts-with(text(), "Diagnóstico codificado ")]]');
    expect($diagnosticRows)->toHaveCount(2);
    foreach ($diagnosticRows as $index => $diagnosticRow) {
        $cells = $diagnosticRow->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->c;
        $diagnosis = $result->diagnoses()->orderBy('position')->get()[$index];
        expect((string) $cells[1]->is->t)->toBe(trim($diagnosis->code.' - '.$diagnosis->description));
    }
    $zip->close();

    $generalResponse = $this->actingAs($user)->get(route('resultados.excel.consolidado'));
    $generalResponse->assertOk()
        ->assertDownload('Reporte-general-'.now()->format('Y-m-d').'.xlsx')
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $generalPath = $generalResponse->baseResponse->getFile()->getPathname();
    $generalZip = new ZipArchive();
    expect($generalZip->open($generalPath))->toBeTrue();
    expect($generalZip->getFromName('xl/worksheets/sheet1.xml'))
        ->toContain('REPORTE GENERAL DE RESULTADOS')
        ->toContain('PACIENTE RESULTADO')
        ->toContain('orientation="landscape"')
        ->toContain('fitToWidth="1" fitToHeight="0"');
    $generalSheet = simplexml_load_string($generalZip->getFromName('xl/worksheets/sheet1.xml'));
    $generalSheet->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $diagnosisCells = $generalSheet->xpath('//s:row[@r>5]/s:c[starts-with(@r,"H")]/s:is/s:t');
    expect(array_map(fn ($cell) => (string) $cell, $diagnosisCells))
        ->toBe(['MUESTRA SATISFACTORIA', 'NEGATIVO PARA LESION INTRAEPITELIAL']);
    expect($generalZip->getFromName('xl/workbook.xml'))->toContain('_xlnm.Print_Titles')->toContain('_xlnm.Print_Area');
    $generalZip->close();

    // The period follows the request date, even if the result is issued later.
    $result->update(['result_date' => '2027-03-01']);
    foreach ([2 => true, 3 => false] as $month => $included) {
        $monthly = $this->actingAs($user)->get(route('resultados.excel.consolidado', ['month' => $month, 'year' => 2027]));
        $monthly->assertOk()->assertDownload(sprintf('Reporte-resultados-2027-%02d.xlsx', $month));
        $monthlyZip = new ZipArchive();
        $monthlyZip->open($monthly->baseResponse->getFile()->getPathname());
        $xml = $monthlyZip->getFromName('xl/worksheets/sheet1.xml');
        expect(str_contains($xml, 'PACIENTE RESULTADO'))->toBe($included);
        expect($xml)->toContain($month === 2 ? 'Febrero 2027' : 'Marzo 2027');
        $monthlyZip->close();
    }
    $this->actingAs($user)->getJson(route('resultados.excel.consolidado', ['month' => 2]))
        ->assertUnprocessable()->assertJsonValidationErrors('year');
    $result->update(['result_date' => '2027-02-11']);

    $positiveMonthly = $this->actingAs($user)->get(route('resultados.positivos.mensual', [
        'month' => 2, 'year' => 2027,
    ]));
    $positiveMonthly->assertOk()->assertDownload('Casos-positivos-2027-02.xls');
    expect($positiveMonthly->getContent())
        ->toContain('CASOS POSITIVOS FEBRERO 2027')
        ->toContain('PACIENTE RESULTADO');

    foreach (['urban', 'rural', 'age'] as $reportType) {
        $statistical = $this->actingAs($user)->get(route('citologia.reportes.estadistico', [
            'type' => $reportType, 'month' => 2, 'year' => 2027,
        ]));
        $statistical->assertOk()->assertHeader(
            'content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $statisticalZip = new ZipArchive();
        expect($statisticalZip->open($statistical->baseResponse->getFile()->getPathname()))->toBeTrue()
            ->and($statisticalZip->getFromName('xl/worksheets/sheet1.xml'))->toContain('INFORME');
        $statisticalZip->close();
    }
});

test('an existing report can be removed without deleting its request or patient', function () {
    $user = User::factory()->create();
    $patient = Patient::query()->create([
        'identity_document' => '7001002',
        'full_name' => 'PACIENTE INFORME ELIMINABLE',
        'is_active' => true,
    ]);

    $this->actingAs($user)->post(route('solicitudes.store'), [
        'patient_id' => $patient->id,
        'module' => 'U',
        'request_date' => '2027-03-10',
        'sample_collection_date' => '2027-03-10',
        'sample_quantity' => 1,
    ])->assertRedirect();

    $pathologyRequest = PathologyRequest::query()->with('samples')->firstOrFail();
    $sample = $pathologyRequest->samples->first();

    $this->actingAs($user)->post(route('resultados.store', $pathologyRequest), [
        'sample_id' => $sample->id,
        'result_date' => '2027-03-11',
        'status' => 'validated',
        'sample_adequacy' => 'satisfactory',
        'general_category' => 'negative',
        'diagnosis' => 'SIN LESION',
        'diagnosis_entries' => [
            ['code' => 'I', 'description' => 'NEGATIVO PARA LESION INTRAEPITELIAL O MALIGNIDAD'],
        ],
    ])->assertRedirect(route('resultados.index'));

    $result = $sample->pathologyResult()->firstOrFail();

    $this->actingAs($user)->delete(route('resultados.destroy', $result))
        ->assertRedirect(route('resultados.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted('pathology_results', ['id' => $result->id]);
    $this->assertDatabaseHas('pathology_requests', ['id' => $pathologyRequest->id]);
    $this->assertDatabaseHas('patients', ['id' => $patient->id]);

    expect($sample->fresh()->status)->toBe(Sample::STATUS_RECEIVED)
        ->and($pathologyRequest->fresh()->status)->toBe(PathologyRequest::STATUS_PROCESSING);

    $this->actingAs($user)->post(route('resultados.store', $pathologyRequest), [
        'sample_id' => $sample->id,
        'result_date' => '2027-03-12',
        'status' => 'validated',
        'sample_adequacy' => 'satisfactory',
        'general_category' => 'negative',
        'diagnosis_entries' => [
            ['code' => 'G8', 'description' => 'MUESTRA SATISFACTORIA'],
        ],
    ])->assertRedirect(route('resultados.index'));

    $restoredResult = PathologyResult::query()
        ->where('sample_id', $sample->id)
        ->firstOrFail();

    expect($restoredResult->id)->toBe($result->id)
        ->and($restoredResult->deleted_at)->toBeNull()
        ->and($sample->fresh()->status)->toBe(Sample::STATUS_COMPLETED)
        ->and($pathologyRequest->fresh()->status)->toBe(PathologyRequest::STATUS_VALIDATED);

    $this->actingAs($user)->get(route('resultados.index'))
        ->assertOk()
        ->assertSee('Eliminar')
        ->assertDontSee('Eliminar informe')
        ->assertDontSee('Eliminar solicitud');

    $this->actingAs($user)->get(route('resultados.create', $pathologyRequest))
        ->assertOk()
        ->assertSee('Volver al listado');
});

test('a pathology request can be removed while preserving its patient', function () {
    $user = User::factory()->create();
    $patient = Patient::query()->create([
        'identity_document' => '7001003',
        'full_name' => 'PACIENTE SOLICITUD ELIMINABLE',
        'is_active' => true,
    ]);

    $this->actingAs($user)->post(route('solicitudes.store'), [
        'patient_id' => $patient->id,
        'module' => 'U',
        'request_date' => '2027-04-10',
        'sample_collection_date' => '2027-04-10',
        'sample_quantity' => 1,
    ])->assertRedirect();

    $pathologyRequest = PathologyRequest::query()->with('samples')->firstOrFail();
    $sample = $pathologyRequest->samples->first();

    $this->actingAs($user)
        ->delete(route('solicitudes.destroy', $pathologyRequest))
        ->assertRedirect(route('resultados.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted('pathology_requests', ['id' => $pathologyRequest->id]);
    $this->assertSoftDeleted('samples', ['id' => $sample->id]);
    $this->assertDatabaseHas('patients', ['id' => $patient->id]);
});
