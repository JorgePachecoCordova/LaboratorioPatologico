<?php

use App\Models\AnatomopathologyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('shared surnames require choosing an individual report and results are paginated', function () {
    $this->actingAs(User::factory()->create());
    foreach (range(1, 12) as $number) {
        $report = AnatomopathologyReport::create([
            'report_number' => sprintf('%04d-2026', $number),
            'registration_number' => sprintf('%03d-26', $number),
            'patient_name' => 'PACIENTE '.$number.' DEL VALLE',
            'report_date' => '2026-09-29', 'patient_age' => 30,
            'macroscopic_description' => 'MACRO', 'microscopic_description' => 'MICRO',
            'diagnosis' => 'CONCLUSION', 'status' => 'completed',
        ]);
    }
    $this->get(route('anatomopatologia.index', ['q' => 'DEL VALLE']))
        ->assertOk()->assertViewHas('report', null)->assertSee('12 informes encontrados')
        ->assertSee('PACIENTE 12 DEL VALLE')->assertSee('012-26')->assertSee('Siguiente');
    $this->get(route('anatomopatologia.index', ['q' => 'DEL VALLE', 'page' => 2]))
        ->assertOk()->assertSee('PACIENTE 1 DEL VALLE')->assertSee('Página 2 de 2');
    $this->get(route('anatomopatologia.index', ['q' => 'DEL VALLE', 'registro' => $report->id]))
        ->assertOk()->assertViewHas('report', fn ($selected) => $selected->id === $report->id)
        ->assertSee('value="PACIENTE 12 DEL VALLE"', false);
    $this->get(route('anatomopatologia.index', ['q' => 'SIN COINCIDENCIAS']))
        ->assertOk()->assertSee('No hay coincidencias');
});
