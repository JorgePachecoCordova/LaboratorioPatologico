<?php

use App\Models\{CytologyReport, Patient, PathologyRequest, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('cytology search only returns its own requests and reports', function () {
    $this->actingAs(User::factory()->create(['role'=>'cytology_office','is_active'=>true]));
    $patient=Patient::create(['identity_document'=>'8520716','full_name'=>'MARIA LABORATORIO','is_active'=>true]);
    PathologyRequest::create(['patient_id'=>$patient->id,'module'=>'U','sequence_type'=>'U','sequence_year'=>2026,'sequence_number'=>1,'request_number'=>'000001-U','request_type'=>'CITOLOGIA','request_date'=>'2026-10-25','sample_quantity'=>1]);
    $report=CytologyReport::create(['request_number'=>'SC-TEST-2026','report_number'=>'IC-TEST-2026','patient_name'=>'MARIA CITOLOGIA','request_date'=>'2026-10-25','report_date'=>'2026-10-25','sample_adequacy'=>'satisfactory','general_category'=>'other']);
    foreach (['MARIA', 'SC-TEST-2026','IC-TEST-2026'] as $query) {
        $this->get(route('citologia.index',['q'=>$query]))->assertOk()->assertSee('MARIA CITOLOGIA')->assertDontSee('MARIA LABORATORIO')->assertDontSee('cy-lab-results-title')->assertViewHas('matches',fn($matches)=>$matches->total()===1);
    }
    $this->get(route('citologia.index',['q'=>'LABORATORIO']))->assertOk()->assertSee('Sin coincidencias en citología')->assertViewHas('matches',fn($matches)=>$matches->total()===0);
});
