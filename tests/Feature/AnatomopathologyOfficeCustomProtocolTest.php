<?php

use App\Models\AnatomopathologyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('anatomopathology office can add save reopen and print custom protocols', function () {
    $this->actingAs(User::factory()->create(['role' => 'anatomopathology_office']));
    $this->get(route('anatomopatologia.index'))->assertOk()
        ->assertSee('id="ap-cap-open-custom"', false)->assertSee('Añadir protocolo personalizado')
        ->assertDontSee('__add_custom')->assertDontSee('Añadir más protocolos');
    $this->post(route('anatomopatologia.store'), [
        'registration_number' => '987', 'report_date' => '2026-10-25',
        'patient_name' => 'MARIA PEREZ', 'macroscopic_description' => 'MACRO',
        'microscopic_description' => 'MICRO', 'diagnosis' => 'DIAGNOSTICO',
        'cap_protocol_enabled' => '1', 'cap_protocol' => [
            'specimen' => 'BIOPSIA',
            'custom' => [['title' => 'Campo personalizado', 'value' => 'DETALLE DEL PROTOCOLO']],
        ],
    ])->assertSessionHasNoErrors()->assertRedirect();
    $report = AnatomopathologyReport::query()->firstOrFail();
    expect($report->cap_protocol['custom'][0]['title'])->toBe('CAMPO PERSONALIZADO');
    $this->get(route('anatomopatologia.index', ['registro' => $report->id]))
        ->assertOk()->assertSee('CAMPO PERSONALIZADO')->assertSee('DETALLE DEL PROTOCOLO');
    $this->get(route('anatomopatologia.index', ['registro' => $report->id, 'q' => 'MARIA', 'page' => 1]))
        ->assertOk()->assertSee('#ap-report', false)->assertSee('id="ap-report"', false)
        ->assertSee('href="#ap-search-results"', false)->assertSee('INFORME DE MARIA PEREZ');
    $this->get(route('anatomopatologia.imprimir', $report))->assertOk()
        ->assertSee('CAMPO PERSONALIZADO')->assertSee('DETALLE DEL PROTOCOLO');
    $snapshot = $report->fresh()->getAttributes();
    $this->get(route('anatomopatologia.index', ['registro' => $report->id]))
        ->assertOk()->assertDontSee('Informe guardado · Solo lectura')->assertDontSee('Guardar cambios')
        ->assertDontSee('name="action"', false)->assertDontSee('data-cap-remove', false)
        ->assertSee('readonly', false)->assertSee(route('anatomopatologia.imprimir', $report), false);
    $this->putJson(route('anatomopatologia.update', $report), ['diagnosis' => 'ALTERADO'])->assertForbidden();
    $this->get(route('anatomopatologia.index', ['nuevo_para' => $report->id, 'registro' => $report->id, 'q' => 'MARIA']))
        ->assertOk()->assertViewHas('report', null)->assertSee('value="MARIA PEREZ"', false)
        ->assertDontSee('DETALLE DEL PROTOCOLO')->assertDontSee('Guardar cambios');
    $this->post(route('anatomopatologia.store'), [
        'registration_number' => '988', 'report_date' => '2026-10-26',
        'patient_name' => 'MARIA PEREZ', 'macroscopic_description' => 'NUEVO MACRO',
        'microscopic_description' => 'NUEVO MICRO', 'diagnosis' => 'NUEVO DIAGNOSTICO',
    ])->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseCount('anatomopathology_reports', 2);
    expect($report->fresh()->getAttributes())->toBe($snapshot);
});
