<?php

use App\Models\CytologyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('main modules and search actions open for their assigned users', function () {
    foreach ([
        'administrator' => ['inicio', 'solicitudes.index', 'resultados.index', 'citologia.index', 'anatomopatologia.index', 'configuracion.index', 'configuracion.auditoria', 'perfil.edit'],
        'reception' => ['inicio', 'solicitudes.index', 'resultados.index', 'perfil.edit'],
        'result_reader' => ['inicio', 'resultados.index', 'perfil.edit'],
        'cytology_office' => ['inicio', 'citologia.index', 'perfil.edit'],
        'anatomopathology_office' => ['inicio', 'anatomopatologia.index', 'perfil.edit'],
    ] as $role => $routes) {
        $this->actingAs(User::factory()->create(['role' => $role]));
        foreach ($routes as $name) {
            $this->get(route($name))->assertOk();
            if (in_array($name, ['solicitudes.index', 'resultados.index', 'citologia.index', 'anatomopatologia.index'])) {
                $this->get(route($name, ['q' => 'PACIENTE INEXISTENTE']))->assertOk();
            }
        }
    }
});

test('configuration catalogs support adding editing deactivating and reactivating', function () {
    $this->actingAs(User::factory()->create(['role' => 'administrator']));
    $municipality = \App\Models\Municipality::create(['name' => 'MUNICIPIO BASE', 'normalized_name' => 'MUNICIPIO BASE', 'is_active' => true]);
    $district = \App\Models\District::create(['name' => 'DISTRITO BASE', 'normalized_name' => 'DISTRITO BASE', 'municipality_id' => $municipality->id, 'is_active' => true]);
    $area = \App\Models\Area::create(['name' => 'AREA BASE', 'normalized_name' => 'AREA BASE', 'district_id' => $district->id, 'is_active' => true]);
    foreach ([
        ['medicos', \App\Models\Physician::class, ['full_name' => 'MARIA PEREZ'], 'full_name', 'MARIA LOPEZ'],
        ['municipios', \App\Models\Municipality::class, ['name' => 'MUNICIPIO NUEVO'], 'name', 'MUNICIPIO EDITADO'],
        ['distritos', \App\Models\District::class, ['name' => 'DISTRITO NUEVO', 'municipality_id' => $municipality->id], 'name', 'DISTRITO EDITADO'],
        ['areas', \App\Models\Area::class, ['name' => 'AREA NUEVA', 'district_id' => $district->id], 'name', 'AREA EDITADA'],
        ['sectores', \App\Models\Sector::class, ['name' => 'SECTOR NUEVO', 'area_id' => $area->id], 'name', 'SECTOR EDITADO'],
        ['establecimientos', \App\Models\HealthFacility::class, ['name' => 'CENTRO NUEVO', 'municipality_id' => $municipality->id, 'classification' => 'R'], 'name', 'CENTRO EDITADO'],
        ['patologias', \App\Models\PathologyType::class, ['name' => 'PATOLOGIA NUEVA', 'code' => 'audit'], 'name', 'PATOLOGIA EDITADA'],
    ] as [$key, $model, $payload, $column, $edited]) {
        $this->post(route("configuracion.$key.store"), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $item = $model::where($column, $payload[$column])->firstOrFail();
        $payload[$column] = $edited;
        $this->put(route("configuracion.$key.update", $item), $payload)->assertSessionHasNoErrors()->assertRedirect();
        expect($item->fresh()->$column)->toBe($edited);
        foreach ([false, true] as $active) {
            $this->patch(route("configuracion.$key.estado", $item))->assertRedirect();
            expect($item->fresh()->is_active)->toBe($active);
        }
    }
    $this->post(route('configuracion.protocolos.store'), ['name' => 'PROTOCOLO DE AUDITORIA'])->assertSessionHasNoErrors()->assertRedirect();
    $protocol = \App\Models\CapProtocolField::where('normalized_name', 'PROTOCOLO DE AUDITORIA')->firstOrFail();
    foreach ([false, true] as $active) {
        $this->patch(route('configuracion.protocolos.estado', $protocol))->assertRedirect();
        expect($protocol->fresh()->is_active)->toBe($active);
    }
});

test('requests and results save reopen edit and print in all three areas', function (string $module) {
    $this->actingAs(User::factory()->create(['role' => 'administrator']));
    $patient = \App\Models\Patient::create(['full_name' => 'PACIENTE AUDITORIA', 'identity_document' => 'AUD-'.$module, 'is_active' => true]);
    $this->post(route('solicitudes.store'), [
        'patient_id' => $patient->id, 'module' => $module, 'request_date' => '2026-11-01',
        'sample_collection_date' => '2026-11-01', 'sample_quantity' => 1,
    ])->assertSessionHasNoErrors()->assertRedirect();
    $request = \App\Models\PathologyRequest::where('patient_id', $patient->id)->firstOrFail();
    $this->get(route('solicitudes.show', $request))->assertOk();
    $this->get(route('resultados.create', $request))->assertOk();
    $this->postJson(route('resultados.diagnosticos.store'), ['code' => 'AUD-'.$module, 'description' => 'DIAGNOSTICO DE AUDITORIA'])->assertCreated();
    $payload = [
        'sample_id' => $request->samples->first()->id, 'result_date' => '2026-11-01',
        'reader' => 'ELENA', 'status' => 'validated', 'notes' => 'COMENTARIO DE AUDITORIA', 'expected_revision' => 0,
        'diagnosis_entries' => [['code' => 'AUD-'.$module, 'description' => 'DIAGNOSTICO DE AUDITORIA']],
    ];
    $this->post(route('resultados.store', $request), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $result = \App\Models\PathologyResult::where('sample_id', $payload['sample_id'])->firstOrFail();
    $payload['expected_revision'] = $result->revision;
    $payload['notes'] = 'COMENTARIO ACTUALIZADO';
    $this->post(route('resultados.store', $request), $payload)->assertSessionHasNoErrors()->assertRedirect();
    expect($result->fresh()->notes)->toBe('COMENTARIO ACTUALIZADO');
    $this->get(route('resultados.imprimir', $result))->assertOk()->assertSee('DIAGNOSTICO DE AUDITORIA')->assertSee('01/11/2026')->assertDontSee('<th>Vez</th>', false);
    $this->get(route('solicitudes.registro-diario', ['module' => $module, 'date' => '2026-11-01']))->assertOk();
})->with(['R', 'U', 'G']);

test('cytology add catalog save report search print and excel work together', function () {
    $this->actingAs(User::factory()->create(['role' => 'cytology_office']));
    $serviceId = null;
    foreach (['service' => 'SERVICIO DE PRUEBA', 'physician' => 'MARIA PEREZ', 'sample' => 'MUESTRA DE PRUEBA', 'care' => 'ATENCION DE PRUEBA'] as $type => $name) {
        $response = $this->postJson(route('citologia.catalogos.store'), compact('type', 'name'))
            ->assertCreated()->assertJsonPath('option.name', $name);
        if ($type === 'service') $serviceId = $response->json('option.id');
        $this->postJson(route('citologia.catalogos.store'), compact('type', 'name'))->assertUnprocessable();
    }
    $this->post(route('citologia.store'), [
        'request_date' => '2026-11-01', 'patient_name' => 'PACIENTE DE PRUEBA',
        'patient_age' => 35, 'hospital_service_id' => $serviceId,
        'sample_type_code' => 'MUESTRA DE PRUEBA', 'care_type' => 'ATENCION DE PRUEBA',
        'report_action' => 'validate', 'description' => 'DESCRIPCION DE PRUEBA', 'conclusion' => 'CONCLUSION DE PRUEBA',
    ])->assertSessionHasNoErrors()->assertRedirect();
    $report = CytologyReport::firstOrFail();
    expect($report->request_number)->not->toBeNull();
    expect($report->report_number)->not->toBeNull();
    $this->get(route('citologia.index', ['registro' => $report->id]))->assertOk()->assertSee('PACIENTE DE PRUEBA');
    $this->get(route('citologia.index', ['q' => 'PACIENTE']))->assertOk()->assertSee('PACIENTE DE PRUEBA');
    $this->get(route('citologia.imprimir', $report))->assertOk()->assertSee('CONCLUSION DE PRUEBA');
    $this->get(route('citologia.solicitud.imprimir', $report))->assertOk();
    $this->get(route('citologia.excel', $report))->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('administrator report and configuration actions respond with current permissions', function () {
    $this->actingAs(User::factory()->create(['role' => 'administrator']));
    foreach (['urban', 'rural', 'age'] as $type) {
        foreach (['print', 'excel'] as $format) {
            $this->get(route('citologia.reportes.estadistico', ['year' => 2026, 'month' => 11, 'type' => $type, 'format' => $format]))->assertOk();
        }
    }
    foreach (['print', 'excel'] as $format) {
        $this->get(route('resultados.positivos.mensual', ['year' => 2026, 'month' => 11, 'format' => $format]))->assertOk();
    }
    $this->post(route('configuracion.usuarios.store'), [
        'name' => 'USUARIO DE PRUEBA', 'username' => 'auditoria_botones', 'role' => 'reception',
        'password' => 'Prueba_123', 'password_confirmation' => 'Prueba_123',
    ])->assertSessionHasNoErrors()->assertRedirect();
    $user = User::where('username', 'auditoria_botones')->firstOrFail();
    $this->put(route('configuracion.usuarios.update', $user), [
        'name' => 'USUARIO EDITADO', 'username' => $user->username, 'role' => 'result_reader',
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($user->fresh()->role)->toBe('result_reader');
    $this->patch(route('configuracion.usuarios.estado', $user))->assertRedirect();
    expect($user->fresh()->is_active)->toBeFalse();
});
