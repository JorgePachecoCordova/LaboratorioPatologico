<?php

use App\Models\AnatomopathologyReport;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('anatomopathology opens with a blank form even when reports exist', function () {
    $user = User::factory()->create();
    AnatomopathologyReport::query()->create([
        'report_number' => '0001-2026',
        'report_date' => '2026-08-25',
        'patient_name' => 'PACIENTE ANTERIOR',
        'macroscopic_description' => 'TEXTO ANTERIOR',
        'microscopic_description' => 'TEXTO ANTERIOR',
        'diagnosis' => 'TEXTO ANTERIOR',
        'status' => 'completed',
    ]);

    $this->actingAs($user)
        ->get(route('anatomopatologia.index'))
        ->assertOk()
        ->assertSee('NUMERACIÓN AUTOMÁTICA')
        ->assertDontSee('PACIENTE ANTERIOR');
});

test('saving an anatomopathology report keeps the patient and saved content visible', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('anatomopatologia.store'), [
        'report_date' => '2026-08-25',
        'patient_name' => 'PACIENTE NUEVO',
        'macroscopic_description' => 'DESCRIPCION MACROSCOPICA',
        'microscopic_description' => 'DESCRIPCION MICROSCOPICA',
        'diagnosis' => 'CONCLUSION',
        'action' => 'save',
    ]);

    $report = AnatomopathologyReport::query()->latest('id')->firstOrFail();
    $response->assertRedirect(route('anatomopatologia.index', ['registro' => $report->id]));
    $this->assertDatabaseHas('anatomopathology_reports', ['patient_name' => 'PACIENTE NUEVO']);

    $this->followRedirects($response)
        ->assertOk()
        ->assertSee('Informe 0001-2026 guardado correctamente')
        ->assertSee('Guardar cambios')
        ->assertSee('DESCRIPCION MACROSCOPICA')
        ->assertSee('DESCRIPCION MICROSCOPICA')
        ->assertSee('CONCLUSION')
        ->assertSee('value="PACIENTE NUEVO"', false);
    $this->assertDatabaseCount('anatomopathology_reports', 1);
});

test('updating a report keeps the same report open without duplicating it', function () {
    $user = User::factory()->create();
    $report = AnatomopathologyReport::query()->create([
        'report_number' => '0001-2026', 'report_date' => '2026-08-25',
        'patient_name' => 'PACIENTE ANTERIOR', 'macroscopic_description' => 'MACRO',
        'microscopic_description' => 'MICRO', 'diagnosis' => 'DIAGNOSTICO', 'status' => 'completed',
    ]);

    $response = $this->actingAs($user)->put(route('anatomopatologia.update', $report), [
        'report_date' => '2026-08-25', 'patient_name' => 'PACIENTE MODIFICADO',
        'macroscopic_description' => 'MACRO', 'microscopic_description' => 'MICRO',
        'diagnosis' => 'DIAGNOSTICO', 'action' => 'save',
    ]);

    $report = AnatomopathologyReport::query()->latest('id')->firstOrFail();
    $response->assertRedirect(route('anatomopatologia.index', ['registro' => $report->id]));
    $this->followRedirects($response)->assertSee('Guardar cambios')->assertSee('value="PACIENTE MODIFICADO"', false);
    $this->assertDatabaseCount('anatomopathology_reports', 1);
    $this->get(route('anatomopatologia.index', ['nuevo' => 1]))->assertSee('NUMERACIÓN AUTOMÁTICA')->assertDontSee('value="PACIENTE MODIFICADO"', false);
});

test('search text is normalized to uppercase', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('anatomopatologia.index', ['q' => 'ariel tolay']))
        ->assertOk()->assertSee('value="ARIEL TOLAY"', false);
});

test('anatomopathology reports can be searched by name surname and registration number', function (string $query) {
    $user = User::factory()->create();
    Patient::query()->create([
        'identity_document' => '8520716',
        'full_name' => 'MARIA ELENA PACHECO CORDOVA',
        'is_active' => true,
    ]);
    AnatomopathologyReport::query()->create([
        'report_number' => '0042-2026',
        'registration_number' => 'REG-8791',
        'report_date' => '2026-09-05',
        'patient_name' => 'MARIA ELENA PACHECO CORDOVA',
        'macroscopic_description' => 'MACRO',
        'microscopic_description' => 'MICRO',
        'diagnosis' => 'DIAGNOSTICO',
        'status' => 'completed',
    ]);

    $this->actingAs($user)
        ->get(route('anatomopatologia.index', ['q' => $query]))
        ->assertOk()
        ->assertSee('0042-2026')
        ->assertSee('MARIA ELENA PACHECO CORDOVA');

    $this->get(route('anatomopatologia.index', ['q' => '8520716']))
        ->assertOk()->assertDontSee('value="MARIA ELENA PACHECO CORDOVA"', false);
})->with([
    'nombre' => 'MARÍA ELENA',
    'apellido' => 'PACHECO',
    'numero de registro' => 'REG-8791',
]);

test('numeric searches do not open a report whose registration number only contains the digits', function () {
    $user = User::factory()->create();
    AnatomopathologyReport::query()->create([
        'report_number' => '0002-2026',
        'registration_number' => '12345',
        'report_date' => '2026-09-05',
        'patient_name' => 'MARIEL SANCHEZ',
        'macroscopic_description' => 'MACRO',
        'microscopic_description' => 'MICRO',
        'diagnosis' => 'DIAGNOSTICO',
        'status' => 'completed',
    ]);

    $this->actingAs($user)
        ->get(route('anatomopatologia.index', ['q' => '123']))
        ->assertOk()
        ->assertSee('NUMERACIÓN AUTOMÁTICA')
        ->assertDontSee('value="12345"', false);
});

test('printing after saving opens the automatic browser print flow', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->post(route('anatomopatologia.store'), [
        'report_date' => '2026-09-01', 'patient_name' => 'PACIENTE PARA IMPRESION',
        'macroscopic_description' => 'MACRO', 'microscopic_description' => 'MICRO',
        'diagnosis' => 'DIAGNOSTICO', 'action' => 'print',
    ]);

    $report = AnatomopathologyReport::query()->latest('id')->firstOrFail();
    $response->assertRedirect(route('anatomopatologia.imprimir', ['anatomopatologia' => $report, 'auto' => 1]));
    $this->followRedirects($response)->assertSee("window.print()", false);
});

test('print layout keeps medium blocks together and safely splits oversized text', function () {
    $user = User::factory()->create();
    $report = AnatomopathologyReport::query()->create([
        'report_number' => '0099-2026',
        'report_date' => '2026-09-08',
        'patient_name' => 'PACIENTE SALTO PAGINA',
        'important_data' => str_repeat('TEXTO MEDIANO ', 40),
        'macroscopic_description' => str_repeat('TEXTO EXTENSO ', 200),
        'microscopic_description' => 'MICROSCOPICO BREVE',
        'diagnosis' => 'CONCLUSION BREVE',
        'status' => 'completed',
    ]);

    $this->actingAs($user)
        ->get(route('anatomopatologia.imprimir', $report))
        ->assertOk()
        ->assertSee('size: A4 portrait;', false)
        ->assertSee('margin: 10mm;', false)
        ->assertSee('.page { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }', false)
        ->assertSee('break-inside: avoid-page', false)
        ->assertSee('section macro long-section', false)
        ->assertDontSee('field wide long-field', false);
});

test('anatomopathology correlation restarts at one every year', function () {
    $user = User::factory()->create();
    $payload = fn (string $date) => [
        'report_date' => $date, 'patient_name' => 'PACIENTE',
        'macroscopic_description' => 'MACRO', 'microscopic_description' => 'MICRO',
        'diagnosis' => 'DIAGNOSTICO', 'action' => 'save',
    ];

    $this->actingAs($user)->post(route('anatomopatologia.store'), $payload('2026-12-31'));
    $this->actingAs($user)->post(route('anatomopatologia.store'), $payload('2026-12-31'));
    $this->actingAs($user)->post(route('anatomopatologia.store'), $payload('2027-01-01'));

    expect(AnatomopathologyReport::query()->orderBy('id')->pluck('report_number')->all())
        ->toBe(['0001-2026', '0002-2026', '0001-2027']);
});

test('office accounts can only enter their assigned module', function () {
    $anatomyUser = User::factory()->create(['role' => 'anatomopathology_office']);

    $this->actingAs($anatomyUser)->get(route('anatomopatologia.index'))->assertOk();
    $this->actingAs($anatomyUser)->get(route('citologia.index'))->assertForbidden();
    $this->actingAs($anatomyUser)->get(route('solicitudes.index'))->assertForbidden();
});
