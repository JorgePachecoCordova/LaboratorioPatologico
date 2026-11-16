<?php

use App\Models\AnatomopathologyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('registration numbers automatically use the report date and a two digit year on creation and editing', function ($input, $expected) {
    $this->actingAs(User::factory()->create());
    $payload = [
        'registration_number' => $input, 'report_date' => '2026-09-25',
        'patient_name' => 'PACIENTE', 'macroscopic_description' => 'MACRO',
        'microscopic_description' => 'MICRO', 'diagnosis' => 'DIAGNOSTICO',
    ];
    $this->post(route('anatomopatologia.store'), $payload)->assertSessionHasNoErrors();
    $report = AnatomopathologyReport::firstOrFail();
    expect($report->registration_number)->toBe($expected);
    $payload['registration_number'] = '2';
    $payload['report_date'] = '2027-01-01';
    $this->put(route('anatomopatologia.update', $report), $payload)->assertSessionHasNoErrors();
    expect($report->fresh()->registration_number)->toBe('002-27');
})->with([
    ['1', '001-26'], ['001-26', '001-26'], ['001-2026', '001-26'],
    ['1250', '1250-26'], ['003-2025', '003-26'],
]);
