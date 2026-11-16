<?php

use App\Models\AnatomopathologyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('anatomopathology reports can be saved and printed with optional study fields', function (array $text) {
    $this->actingAs(User::factory()->create(['role' => 'anatomopathology_office']));
    $this->post(route('anatomopatologia.store'), [
        'registration_number' => '1',
        'report_date' => '2026-11-10',
        'patient_name' => 'PACIENTE PRUEBA',
    ] + $text)->assertSessionHasNoErrors()->assertRedirect();

    $report = AnatomopathologyReport::firstOrFail();
    expect($report->macroscopic_description)->toBeNull();
    expect($report->microscopic_description)->toBeNull();
    expect($report->diagnosis)->toBeNull();
    $this->get(route('anatomopatologia.imprimir', $report))->assertOk();
})->with([
    'omitted' => [[]],
    'empty' => [[
        'macroscopic_description' => '',
        'microscopic_description' => '  ',
        'diagnosis' => '',
    ]],
]);

test('CAP can be included without a specimen or procedure', function (array $protocol) {
    $this->actingAs(User::factory()->create(['role' => 'anatomopathology_office']));
    $this->post(route('anatomopatologia.store'), [
        'registration_number' => '1',
        'report_date' => '2026-11-10',
        'patient_name' => 'PACIENTE PRUEBA',
        'cap_protocol_enabled' => 1,
        'cap_protocol' => $protocol,
    ])->assertSessionHasNoErrors()->assertRedirect();

    $report = AnatomopathologyReport::firstOrFail();
    expect($report->cap_protocol_enabled)->toBeTrue();
    $this->get(route('anatomopatologia.imprimir', $report))->assertOk();
    $this->get(route('anatomopatologia.index'))
        ->assertOk()->assertDontSee('Muestra / procedimiento *')
        ->assertSee('Muestra / procedimiento es opcional.');
})->with([
    'not selected' => [[]],
    'blank' => [['specimen' => '']],
]);
