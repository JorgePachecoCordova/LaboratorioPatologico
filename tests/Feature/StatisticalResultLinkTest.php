<?php

use App\Models\{User, Patient, PathologyRequest, PathologyResult, Municipality, District, HealthFacility};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('reports include results without study labels and inherit facility geography', function ($module, $type) {
    $this->actingAs(User::factory()->create(['role' => 'administrator']));
    $municipality = Municipality::create(['name'=>'MUNICIPIO PRUEBA','normalized_name'=>'MUNICIPIO PRUEBA']);
    $district = District::create(['name'=>'RED PRUEBA','normalized_name'=>'RED PRUEBA']);
    $facility = HealthFacility::create(['name'=>'CENTRO PRUEBA','normalized_name'=>'CENTRO PRUEBA', 'municipality_id'=>$municipality->id,'district_id'=>$district->id]);
    $patient = Patient::create(['full_name'=>'PACIENTE PRUEBA','identity_document'=>'TEST','birth_date'=>'2000-01-01','is_active'=>true]);
    $this->post(route('solicitudes.store'), ['patient_id'=>$patient->id,'module'=>$module,'request_date'=>'2026-09-20','sample_collection_date'=>'2026-09-20','sample_quantity'=>1])->assertSessionHasNoErrors();
    $request = PathologyRequest::firstOrFail();
    $request->update(['health_facility_id'=>$facility->id,'municipality_id'=>null,'district_id'=>null,'request_type'=>null,'study_type'=>null]);
    $result = PathologyResult::create(['sample_id'=>$request->samples->first()->id,'result_date'=>'2026-10-03','diagnosis'=>'DIAGNOSTICO PRUEBA','is_positive'=>true,'status'=>'validated']);
    $response = $this->get(route('citologia.reportes.estadistico',['year'=>2026,'month'=>10,'type'=>$type,'format'=>'print']))->assertOk();
    foreach (['2026-01-01', '2026-12-31', '2025-12-31', '2027-01-01'] as $date) {
        $sample = $request->samples->first()->replicate();
        $sample->sample_number = 10 + (int) substr($date, 0, 4) * 10000 + (int) str_replace('-', '', substr($date, 5));
        $sample->save();
        PathologyResult::create(['sample_id'=>$sample->id, 'result_date'=>$date, 'diagnosis'=>'LIMITE '.$date, 'is_positive'=>true, 'status'=>'validated']);
    }
    $annual = $this->get(route('citologia.reportes.estadistico', ['year'=>2026, 'period'=>'annual', 'type'=>$type, 'format'=>'print']))
        ->assertOk()->assertSee('INFORME ANUAL')->assertSee('ENERO A DICIEMBRE');
    $this->get(route('citologia.reportes.estadistico', ['year'=>2026, 'period'=>'annual', 'type'=>$type, 'format'=>'excel']))
        ->assertOk()->assertDownload('Informe-citologia-'.$type.'-2026-anual.xlsx');
    $annual->assertViewHas('rows', function ($rows) use ($type) {
        if ($type === 'age') { return collect($rows)->where(2, 'MUESTRAS DE PAP TOMADAS')->sum(fn ($row) => end($row)) === 3; }
        return end($rows)[count($rows[0]) - 1] === 3;
    });
    $this->get(route('resultados.positivos.mensual', ['year'=>2026, 'period'=>'annual', 'format'=>'print']))
        ->assertOk()->assertSee('CONSOLIDADO ANUAL')->assertSee('LIMITE 2026-01-01')->assertSee('LIMITE 2026-12-31')
        ->assertDontSee('LIMITE 2025-12-31')->assertDontSee('LIMITE 2027-01-01');
    $this->get(route('resultados.positivos.mensual', ['year'=>2026, 'period'=>'annual', 'format'=>'excel']))
        ->assertOk()->assertDownload('Casos-positivos-2026-anual.xlsx');
    if ($type === 'rural') $response->assertSee('MUNICIPIO PRUEBA')->assertSee('RED PRUEBA');
    if ($type === 'urban') $response->assertSee('CENTRO PRUEBA');
    $this->get(route('resultados.positivos.mensual',['year'=>2026,'month'=>10,'format'=>'print']))->assertOk()->assertSee('PACIENTE PRUEBA')->assertSee('MUNICIPIO PRUEBA');
    $this->get(route('resultados.positivos.mensual',['year'=>2026,'month'=>9,'format'=>'print']))->assertOk()->assertDontSee('PACIENTE PRUEBA');
})->with([['R','rural'],['U','urban'],['G','age']]);
