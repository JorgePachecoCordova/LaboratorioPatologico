<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('a missing patient name is prefilled as the full name and not as the identity document', function () {
    $response = $this->actingAs(User::factory()->create())
        ->get(route('solicitudes.index', ['patient_query' => 'PEDRO']));

    $response->assertOk();

    $html = $response->getContent();

    expect($html)
        ->toMatch('/name="identity_document"[\s\S]*?value=""/')
        ->and($html)->toMatch('/name="full_name"[\s\S]*?value="PEDRO"/');
});

test('a missing numeric patient query is prefilled as the identity document', function () {
    $response = $this->actingAs(User::factory()->create())
        ->get(route('solicitudes.index', ['patient_query' => '1234567-1']));

    $response->assertOk();

    $html = $response->getContent();

    expect($html)
        ->toMatch('/name="identity_document"[\s\S]*?value="1234567-1"/')
        ->and($html)->toMatch('/name="full_name"[\s\S]*?value=""/');
});

test('patient selection is shown before module selection and every module opens its detail dialog', function () {
    $response = $this->actingAs(User::factory()->create())
        ->get(route('solicitudes.index'));

    $response->assertOk()
        ->assertSeeInOrder(['Buscar paciente', 'Paciente seleccionado', 'Tipo de solicitud'])
        ->assertSee("abrirAreaSolicitud('U')", false)
        ->assertSee("abrirAreaSolicitud('R')", false)
        ->assertSee("abrirAreaSolicitud('G')", false)
        ->assertSee('window.abrirAreaSolicitud', false)
        ->assertSee("detailsDialog?.classList.remove('module-dialog-dismissed')", false)
        ->assertSee('id="module_details_dialog"', false);
});

test('a selected patient can be changed from the request screen', function () {
    $patient = \App\Models\Patient::query()->create([
        'identity_document' => '8520716',
        'full_name' => 'JORGE PACHECO CORDOVA',
        'is_active' => true,
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('solicitudes.index', ['patient_query' => $patient->identity_document]));

    $response->assertOk()
        ->assertSee('Cambiar paciente')
        ->assertSee('href="' . route('solicitudes.index') . '"', false);
});

test('a name search lists matches instead of selecting the first patient automatically', function () {
    \App\Models\Patient::query()->create([
        'identity_document' => '1001',
        'full_name' => 'JORGE PACHECO CORDOVA',
        'is_active' => true,
    ]);
    \App\Models\Patient::query()->create([
        'identity_document' => '1002',
        'full_name' => 'JORGE RAMIREZ FLORES',
        'is_active' => true,
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('solicitudes.index', ['patient_query' => 'JORGE']));

    $response->assertOk()
        ->assertSee('Seleccione el paciente correcto')
        ->assertSee('JORGE PACHECO CORDOVA')
        ->assertSee('JORGE RAMIREZ FLORES')
        ->assertDontSee('PACIENTE SELECCIONADO: JORGE PACHECO CORDOVA');
});

test('an exact full name selects the patient and name terms work in any order', function () {
    $patient = \App\Models\Patient::query()->create([
        'identity_document' => '8520716',
        'full_name' => 'JORGE PACHECO CORDOVA',
        'is_active' => true,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('solicitudes.index', ['patient_query' => '  JORGE   PACHECO CORDOVA ']))
        ->assertOk()
        ->assertSee('PACIENTE SELECCIONADO: JORGE PACHECO CORDOVA');

    $this->actingAs(User::factory()->create())
        ->get(route('solicitudes.index', ['patient_query' => 'PACHECO JORGE']))
        ->assertOk()
        ->assertSee('Seleccione el paciente correcto')
        ->assertSee($patient->identity_document);
});
