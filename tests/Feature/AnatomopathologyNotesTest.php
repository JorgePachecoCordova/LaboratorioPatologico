<?php

use App\Models\AnatomopathologyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('optional notes persist, appear in the report and can be cleared', function () {
    $this->actingAs(User::factory()->create());
    $data = ['report_date' => '2026-09-30', 'patient_name' => 'PACIENTE PRUEBA',
        'macroscopic_description' => 'MACRO', 'microscopic_description' => 'MICRO',
        'diagnosis' => 'CONCLUSION', 'notes' => 'nota de prueba'];
    $this->post(route('anatomopatologia.store'), $data)->assertSessionHasNoErrors();
    $report = AnatomopathologyReport::firstOrFail();
    expect($report->notes)->toBe('NOTA DE PRUEBA');
    $this->get(route('anatomopatologia.index', ['registro' => $report->id]))->assertSee('NOTA DE PRUEBA');
    $this->get(route('anatomopatologia.imprimir', $report))
        ->assertSeeInOrder(['CONCLUSIÓN', 'NOTA DE PRUEBA', 'MÉDICO PATÓLOGO']);
    $data['notes'] = '';
    $this->put(route('anatomopatologia.update', $report), $data)->assertSessionHasNoErrors();
    expect($report->fresh()->notes)->toBeNull();
    $this->get(route('anatomopatologia.imprimir', $report))->assertDontSee('NOTA:');
});
