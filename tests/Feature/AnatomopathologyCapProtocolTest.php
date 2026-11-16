<?php

use App\Models\AnatomopathologyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function capReportPayload(): array
{
    return ['report_date' => '2026-10-09', 'patient_name' => 'PACIENTE DEMO',
        'macroscopic_description' => 'MACRO', 'microscopic_description' => 'MICRO',
        'diagnosis' => 'CONCLUSION'];
}

test('CAP is optional and legacy reports print without an annex', function () {
    $this->actingAs(User::factory()->create());
    $this->post(route('anatomopatologia.store'), capReportPayload())->assertSessionHasNoErrors();
    $report = AnatomopathologyReport::firstOrFail();
    expect($report->cap_protocol_enabled)->toBeFalse();
    $this->get(route('anatomopatologia.imprimir', $report))->assertDontSee('PROTOCOLO CAP');
});

test('CAP persists and prints after the original report with patient identification', function () {
    $this->actingAs(User::factory()->create());
    $data = capReportPayload() + ['cap_protocol_enabled' => '1',
        'cap_protocol' => ['specimen' => 'muestra de demostración', 'margins' => '<script>alert(1)</script>', 'staging' => 'dato manual']];
    $this->post(route('anatomopatologia.store'), $data)->assertSessionHasNoErrors();
    $report = AnatomopathologyReport::firstOrFail();
    expect($report->cap_protocol['specimen'])->toBe('MUESTRA DE DEMOSTRACION');
    $this->get(route('anatomopatologia.index', ['registro' => $report->id]))
        ->assertSee('MUESTRA DE DEMOSTRACION')->assertSee('Incluir protocolo')
        ->assertDontSee('Órgano / sitio primario')->assertDontSee('Protocolo y versión de referencia');
    $this->get(route('anatomopatologia.imprimir', $report))
        ->assertSeeInOrder(['CONCLUSIÓN', 'MÉDICO PATÓLOGO', 'PROTOCOLO CAP', 'PACIENTE DEMO', 'MUESTRA DE DEMOSTRACION'])
        ->assertSee('break-before: page', false)->assertDontSee('<SCRIPT>', false);
    $this->put(route('anatomopatologia.update', $report), capReportPayload())->assertSessionHasNoErrors();
    expect($report->fresh()->cap_protocol_enabled)->toBeTrue();
    $data['cap_protocol_enabled'] = '0';
    $this->put(route('anatomopatologia.update', $report), $data)->assertSessionHasNoErrors();
    expect($report->fresh()->cap_protocol['specimen'])->toBe('MUESTRA DE DEMOSTRACION');
    $this->get(route('anatomopatologia.imprimir', $report))->assertDontSee('PROTOCOLO CAP');
    $data['cap_protocol_enabled'] = '1';
    $data['action'] = 'print';
    $this->put(route('anatomopatologia.update', $report), $data)
        ->assertRedirect(route('anatomopatologia.imprimir', ['anatomopatologia' => $report, 'auto' => 1]));
    $this->assertDatabaseCount('anatomopathology_reports', 1);
});

test('CAP validates specimen field types length and allowed keys', function () {
    $this->actingAs(User::factory()->create());
    $this->post(route('anatomopatologia.store'), capReportPayload() + ['cap_protocol_enabled' => '1'])
        ->assertSessionHasErrors('cap_protocol.specimen');
    $this->post(route('anatomopatologia.store'), capReportPayload() + ['cap_protocol' => ['specimen' => ['invalid']]])
        ->assertSessionHasErrors('cap_protocol.specimen');
    $this->post(route('anatomopatologia.store'), capReportPayload() + ['cap_protocol' => ['note' => str_repeat('a', 10001)]])
        ->assertSessionHasErrors('cap_protocol.note');
    $this->post(route('anatomopatologia.store'), capReportPayload() + ['cap_protocol' => ['unknown' => 'invalid']])
        ->assertSessionHasErrors('cap_protocol');
    $this->assertDatabaseCount('anatomopathology_reports', 0);
});
