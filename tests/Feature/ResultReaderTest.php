<?php

use App\Models\Patient;
use App\Models\PathologyRequest;
use App\Models\PathologyResult;
use App\Models\User;
use App\Support\PathologyResultExcelExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('reader is saved and colored only in the daily register', function (string $module) {
    $this->actingAs(User::factory()->create());
    $patient = Patient::create(['identity_document' => 'READER-'.$module, 'full_name' => 'PACIENTE PRUEBA', 'is_active' => true]);
    foreach (PathologyResult::READERS as $reader => $color) {
    $this->post(route('solicitudes.store'), [
        'reader' => $reader,
        'patient_id' => $patient->id, 'module' => $module, 'request_date' => '2026-09-25',
        'sample_collection_date' => '2026-09-25', 'sample_quantity' => 1,
    ])->assertSessionHasNoErrors();
    $request = PathologyRequest::where('patient_id', $patient->id)->latest('id')->firstOrFail();
    expect($request->reader)->toBe($reader);
    $payload = ['sample_id' => $request->samples->first()->id, 'result_date' => '2026-09-25', 'diagnosis' => 'DIAGNOSTICO DE PRUEBA', 'status' => 'validated'];
    $payload['diagnosis_entries'] = [['code' => 'PRUEBA', 'description' => 'DIAGNOSTICO DE PRUEBA']];
        $this->post(route('resultados.store', $request), $payload)->assertSessionHasNoErrors();
        $result = PathologyResult::where('sample_id', $payload['sample_id'])->firstOrFail();
        expect($result->reader)->toBeNull();
        $this->get(route('solicitudes.registro-diario', ['module' => $module, 'date' => '2026-09-25']))
            ->assertOk()->assertDontSee('LECTOR')->assertDontSee($reader)->assertSee('color:'.$color, false);
        $this->get(route('resultados.imprimir', $result))->assertOk()->assertDontSee($reader)->assertDontSee('LECTOR');
        $result->update(['is_positive' => true]);
        foreach ([0, 1] as $printing) {
            $this->get(route('solicitudes.registro-diario', ['module' => $module, 'date' => '2026-09-25', 'print' => $printing]))
                ->assertOk()
                ->assertDontSee($reader)
                ->assertDontSee('>POSITIVO<', false)
                ->assertSee('class="result sample-result positive-codes" style="color:'.$color.'"', false)
                ->assertDontSee('text-decoration-line:underline', false);
        }
        $result->update(['is_positive' => false]);
    }
    $path = app(PathologyResultExcelExporter::class)->create($result);
    $zip = new ZipArchive();
    $zip->open($path);
    $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
    expect($xml)->not->toContain('GABRIELA')->not->toContain('LECTOR');
    expect(substr_count($xml, 'DIAGNOSTICO DE PRUEBA'))->toBe(1);
    expect($xml)->not->toContain('Diagnóstico / conclusión');
    $zip->close();
    unlink($path);
    $this->postJson(route('solicitudes.store'), ['reader' => 'NO EXISTE'])->assertUnprocessable()->assertJsonValidationErrors('reader');
    $request->update(['reader' => null]);
    $result->update(['reader' => 'MARIELA']);
    $this->post(route('resultados.store', $request), $payload)->assertSessionHasNoErrors();
    expect($result->fresh()->reader)->toBe('MARIELA');
    $this->get(route('solicitudes.registro-diario', ['module' => $module, 'date' => '2026-09-25']))->assertSee('color:#185abd', false);
})->with(['R', 'U', 'G']);
