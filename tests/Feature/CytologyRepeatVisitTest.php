<?php
use App\Models\{CytologyReport, HospitalService, Physician, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
uses(TestCase::class, RefreshDatabase::class);
test('repeat visit creates an independent request and preserves the previous report', function () {
    $this->actingAs(User::factory()->create(['role'=>'cytology_office']));
    $previous = CytologyReport::query()->create([
        'request_number'=>'SC-000001-2026','request_date'=>'2026-10-01',
        'patient_name'=>'MARIA PEREZ','patient_age'=>30,'clinical_history_number'=>'HC-123',
        'sample_type'=>'LIQUIDO PLEURAL','sample_type_code'=>'LIQUIDO PLEURAL',
        'report_number'=>'001-2026','description'=>'DESCRIPCION ANTERIOR','conclusion'=>'CONCLUSION ANTERIOR','status'=>'validated',
    ]);
    $snapshot=$previous->fresh()->getAttributes();
    $this->get(route('citologia.index',['registro'=>$previous->id,'tab'=>'informe']))
        ->assertOk()->assertSee('Solo lectura')->assertSee('readonly',false)
        ->assertDontSee('Guardar borrador')->assertDontSee('Validar informe')
        ->assertDontSee('Guardar y finalizar informe');
    $this->putJson(route('citologia.informe.update',$previous),[
        'report_date'=>'2026-10-25','description'=>'CAMBIO','conclusion'=>'CAMBIO','report_action'=>'validate',
    ])->assertForbidden();
    $this->putJson(route('citologia.update',$previous),['patient_name'=>'CAMBIO'])->assertForbidden();
    $this->get(route('citologia.index',['registro'=>$previous->id,'tab'=>'solicitud']))
        ->assertOk()->assertSee('Nueva solicitud de este paciente')
        ->assertSee('Continuar al informe')->assertDontSee('Guardar cambios')
        ->assertDontSee('Guardar e imprimir solicitud')->assertDontSee('💾 Guardar solicitud');
    $this->get(route('citologia.index',['nuevo'=>1]))
        ->assertOk()->assertSee('Guardar solicitud');
    $this->get(route('citologia.index',['nueva_para'=>$previous->id,'registro'=>$previous->id]))
        ->assertOk()->assertViewHas('report',null)->assertSee('Nueva atención de MARIA PEREZ')
        ->assertSee('value="HC-123"',false)->assertDontSee('value="LIQUIDO PLEURAL" selected',false)
        ->assertSee('NUMERACIÓN AUTOMÁTICA');
    $this->post(route('citologia.store'),[
        'request_date'=>'2026-10-25','patient_name'=>'MARIA PEREZ','patient_age'=>30,
        'care_type'=>'external','clinical_history_number'=>'HC-123',
        'hospital_service_id'=>HospitalService::query()->create(['name'=>'CLINICA','normalized_name'=>'CLINICA','is_active'=>true])->id,
        'requesting_physician_id'=>Physician::query()->create(['full_name'=>'MEDICO PRUEBA','is_active'=>true])->id,
        'sample_type_code'=>'LIQUIDO ASCITICO','sample_taken_at'=>'08:00','sample_received_at'=>'09:00',
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($previous->fresh()->getAttributes())->toBe($snapshot);
    $new=CytologyReport::query()->latest('id')->firstOrFail();
    expect($new->id)->not->toBe($previous->id);
    expect($new->request_number)->toBe('SC-000002-2026');
    expect($new->report_number)->toBeNull();
    expect($new->description)->toBeNull();
    $this->get(route('citologia.index',['registro'=>$new->id,'tab'=>'informe']))
        ->assertOk()->assertSee('Guardar y finalizar informe')->assertDontSee('Solo lectura');
    $this->assertDatabaseCount('cytology_reports',2);
    $this->get(route('citologia.index',['q'=>'MARIA PEREZ']))->assertOk()
        ->assertSee('SC-000001-2026')->assertSee('SC-000002-2026');
});
