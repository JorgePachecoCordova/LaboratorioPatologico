<?php

use App\Models\{CytologyReport, HealthFacility, Patient, PathologyRequest, PathologyResult, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function statisticResult(string $module, string $date, array $overrides = [], ?string $birth = '2000-10-15'): PathologyResult
{
    $number = PathologyRequest::withTrashed()->count() + 1;
    $patient = Patient::create([
        'full_name' => 'PACIENTE PRUEBA', 'identity_document' => 'STAT-'.$number,
        'birth_date' => $birth, 'is_active' => true,
    ]);
    $request = PathologyRequest::create([
        'patient_id' => $patient->id, 'module' => $module,
        'sequence_type' => $module, 'sequence_year' => 2025, 'sequence_number' => $number,
        'request_number' => str_pad((string) $number, 6, '0', STR_PAD_LEFT).'-'.$module,
        'request_date' => '2025-12-01', 'sample_quantity' => 1, 'status' => 'received',
    ]);
    $sample = $request->samples()->create(['sample_number' => 1, 'sample_type' => 'PAP', 'status' => 'completed']);
    return PathologyResult::create($overrides + [
        'sample_id' => $sample->id, 'result_date' => $date,
        'diagnosis' => 'DIAGNOSTICO PRUEBA', 'status' => 'validated', 'is_positive' => true,
    ]);
}

function statisticRows($case, string $type, string $period = 'monthly', int $month = 10): array
{
    return $case->get(route('citologia.reportes.estadistico', [
        'year' => 2026, 'month' => $month, 'period' => $period, 'type' => $type, 'format' => 'print',
    ]))->assertOk()->viewData('rows');
}

test('all statistical reports use validated results and their result dates', function (string $module, string $type) {
    $this->actingAs(User::factory()->create(['role' => 'cytology_office']));
    statisticResult($module, '2026-01-01');
    statisticResult($module, '2026-10-01');
    statisticResult($module, '2026-10-31');
    statisticResult($module, '2026-12-31');
    statisticResult($module, '2025-12-31');
    statisticResult($module, '2027-01-01');
    statisticResult($module, '2026-10-10', ['status' => 'draft']);
    $cancelled = statisticResult($module, '2026-10-10');
    $cancelled->sample->pathologyRequest->update(['status' => 'cancelled']);
    $deleted = statisticResult($module, '2026-10-10');
    $deleted->delete();
    $deletedSample = statisticResult($module, '2026-10-10');
    $deletedSample->sample->delete();
    $deletedRequest = statisticResult($module, '2026-10-10');
    $deletedRequest->sample->pathologyRequest->delete();
    // Independent cytology reports do not add a laboratory result to statistics.
    CytologyReport::create(['patient_name' => 'INFORME INDEPENDIENTE', 'request_number' => 'SC-TEST-2026', 'status' => 'validated']);

    $rows = statisticRows($this, $type);
    $total = $type === 'age' ? collect($rows)->where(1, 24)->sum(fn ($row) => end($row)) : end($rows)[count($rows[0])-1];
    expect($total)->toBe($type === 'age' ? 2 : 3);
    $annual = statisticRows($this, $type, 'annual');
    $total = $type === 'age' ? collect($annual)->where(1, 24)->sum(fn ($row) => end($row)) : end($annual)[count($annual[0])-1];
    expect($total)->toBe(4);

    $response = $this->get(route('resultados.positivos.mensual', ['year' => 2026, 'month' => 10, 'format' => 'print']))->assertOk();
    expect($response->viewData('rows'))->toHaveCount(2);
    foreach ($response->viewData('rows') as $row) expect($row[6])->toBeInt();
    $annual = $this->get(route('resultados.positivos.mensual', ['year' => 2026, 'period' => 'annual', 'format' => 'print']))->assertOk();
    expect($annual->viewData('rows'))->toHaveCount(4);
})->with([['U', 'urban'], ['R', 'rural'], ['G', 'age']]);

test('age bands reconcile with totals including absent or invalid birth dates', function () {
    $this->actingAs(User::factory()->create(['role' => 'cytology_office']));
    foreach (['2017-10-10', '2016-10-10', '2012-10-10', '2011-10-10', '2007-10-10', '2006-10-10', '1992-10-10', '1991-10-10', '1977-10-10', '1976-10-10', null, '2027-01-01'] as $birth) {
        statisticResult('G', '2026-10-10', [], $birth);
    }
    statisticResult('G', '2026-10-10', ['is_positive' => false], '2000-01-01');
    $rows = statisticRows($this, 'age');
    $all = collect($rows)->first(fn ($row) => $row[1] === 24 && $row[0] === 'GINECOLOGÍA H.D.B.');
    $positive = collect($rows)->first(fn ($row) => $row[1] === 25 && $row[0] === 'GINECOLOGÍA H.D.B.');
    expect(array_slice($all, 3, 7))->toBe([1, 2, 2, 3, 2, 1, 2]);
    expect(end($all))->toBe(13);
    expect(end($positive))->toBe(12);
    foreach ($rows as $row) expect(array_sum(array_slice($row, 3, 7)))->toBe(end($row));
});

test('monthly totals count samples once even with several diagnoses and group establishments', function () {
    $this->actingAs(User::factory()->create(['role' => 'cytology_office']));
    $facility = HealthFacility::create(['name' => 'CENTRO URBANO', 'normalized_name' => 'CENTRO URBANO', 'classification' => 'U']);
    $result = statisticResult('U', '2026-10-10');
    $request = $result->sample->pathologyRequest;
    $request->update(['health_facility_id' => $facility->id, 'sample_quantity' => 2]);
    $result->diagnoses()->createMany([
        ['position' => 1, 'code' => 'A', 'description' => 'HALLAZGO UNO'],
        ['position' => 2, 'code' => 'B', 'description' => 'HALLAZGO DOS'],
    ]);
    $sample = $request->samples()->create(['sample_number' => 2, 'sample_type' => 'PAP', 'status' => 'completed']);
    PathologyResult::create(['sample_id' => $sample->id, 'result_date' => '2026-10-11', 'diagnosis' => 'NEGATIVO', 'is_positive' => false, 'status' => 'validated']);
    $rows = statisticRows($this, 'urban');
    $row = collect($rows)->first(fn ($row) => $row[0] === 'CENTRO URBANO');
    expect($row[10])->toBe(2);
    expect(end($row))->toBe(2);
    $positive = $this->get(route('resultados.positivos.mensual', ['year' => 2026, 'month' => 10, 'format' => 'print']))->assertOk();
    expect($positive->viewData('rows'))->toHaveCount(1);
    expect($positive->viewData('rows')[0][7])->toBe('HALLAZGO UNO / HALLAZGO DOS');
});

test('Excel and print contain the same rows and numeric statistical values', function (string $type) {
    $this->actingAs(User::factory()->create(['role' => 'cytology_office']));
    statisticResult('U', '2026-10-10');
    statisticResult('R', '2026-10-11');
    statisticResult('G', '2026-10-12', [], null);
    $route = $type === 'positive' ? 'resultados.positivos.mensual' : 'citologia.reportes.estadistico';
    $parameters = ['type' => $type, 'year' => 2026, 'month' => 10, 'period' => 'monthly'];
    $print = $this->get(route($route, $parameters + ['format' => 'print']))->assertOk();
    $excel = $this->get(route($route, $parameters + ['format' => 'excel']))->assertOk()->assertDownload();
    $path = $excel->baseResponse->getFile()->getPathname();
    $zip = new ZipArchive();
    expect($zip->open($path))->toBeTrue();
    $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    @unlink($path);
    $document = new DOMDocument();
    expect($document->loadXML($xml))->toBeTrue();
    $xpath = new DOMXPath($document);
    $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $rows = [];
    foreach ($xpath->query('//s:sheetData/s:row') as $row) {
        $values = [];
        foreach ($xpath->query('s:c', $row) as $cell) {
            $values[] = $cell->getAttribute('t') === 'inlineStr'
                ? $xpath->evaluate('string(s:is/s:t)', $cell)
                : $xpath->evaluate('string(s:v)', $cell);
        }
        $rows[] = $values;
    }
    $header = array_search($print->viewData('headers'), $rows, true);
    expect($header)->not->toBeFalse();
    $expected = array_map(fn ($row) => array_map(fn ($value) => (string) ($value ?? ''), $row), $print->viewData('rows'));
    expect(array_slice($rows, $header + 1, count($expected)))->toBe($expected);
    expect($xpath->query('//s:c[not(@t)]/s:v')->length)->toBeGreaterThan(0);
})->with(['urban', 'rural', 'age', 'positive']);

test('statistical report filters reject invalid periods and dates', function () {
    $this->actingAs(User::factory()->create(['role' => 'cytology_office']));
    foreach (['citologia.reportes.estadistico', 'resultados.positivos.mensual'] as $route) {
        foreach ([['year' => 2024], ['month' => 0], ['month' => 13], ['period' => 'invalid'], ['format' => 'invalid']] as $parameters) {
            $this->getJson(route($route, $parameters))->assertUnprocessable()
                ->assertJsonValidationErrors(array_keys($parameters));
        }
    }
});
