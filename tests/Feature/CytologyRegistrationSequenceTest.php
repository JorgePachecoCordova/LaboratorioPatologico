<?php

use App\Services\CytologyNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('cytology registration starts at one and continues across years', function () {
    $service = app(CytologyNumberService::class);
    expect($service->next('registration', 2026))->toBe('1');
    expect($service->next('registration', 2027))->toBe('2');
    DB::table('annual_document_sequences')->where('document_type', 'cytology_registration')
        ->update(['last_number' => 999999]);
    expect($service->next('registration', 2028))->toBe('1000000');
});

test('existing folios are renumbered with history and the next folio follows them', function () {
    foreach ([['003-2026', '12133'], ['001-2026', '012134'], ['002-2026', '1489']] as [$report, $folio]) {
        DB::table('cytology_reports')->insert([
            'report_number' => $report, 'registration_number' => $folio,
            'patient_name' => 'PACIENTE PRUEBA', 'status' => 'validated',
        ]);
    }
    DB::table('annual_document_sequences')->updateOrInsert([
        'document_type' => 'cytology_registration', 'sequence_year' => 2026,
    ], ['last_number' => 12137, 'created_at' => now(), 'updated_at' => now()]);

    $migration = require base_path('database/migrations/2026_11_10_010000_restart_continuous_cytology_registration_numbers.php');
    $migration->up();

    expect(DB::table('cytology_reports')->orderBy('report_number')->pluck('registration_number')->all())
        ->toBe(['1', '2', '3']);
    expect(DB::table('system_audits')->where('action', 'registration_renumbered')->count())->toBe(3);
    expect(app(CytologyNumberService::class)->next('registration', 2027))->toBe('4');
});
