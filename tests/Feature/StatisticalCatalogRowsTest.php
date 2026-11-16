<?php

use App\Models\{User, Municipality, District, HealthFacility};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('imported municipality names resolve to the reference network without duplicate prefixes', function ($input, $network, $label) {
    expect(\App\Support\PapReportGeography::pair('OCURI', $input))->toBe([$network, $label]);
})->with([
    ['ATOCHA - ATOCHA', 'TUPIZA', 'ATOCHA'],
    ['POTOSI (URBANO) - TOMAVE', 'POTOSI RURAL', 'TOMAVE'],
    ['SACACA - ACACIO', 'SACACA', 'ACACIO'],
    ['SAN PEDRO DE QUEMES- SAN PEDRO DE QUEMES', 'UYUNI', 'SAN P. DE QUEMES'],
]);

test('rural statistics include all catalog relationships with zero counts', function ($period) {
    $this->actingAs(User::factory()->create(['role' => 'cytology_office']));
    $municipality = Municipality::create(['name'=>'MUNICIPIO SIN CASOS', 'normalized_name'=>'MUNICIPIO SIN CASOS']);
    $district = District::create(['name'=>'RED SIN CASOS', 'normalized_name'=>'RED SIN CASOS', 'municipality_id'=>$municipality->id]);
    HealthFacility::create(['name'=>'CENTRO RURAL', 'normalized_name'=>'CENTRO RURAL', 'classification'=>'R', 'municipality_id'=>$municipality->id, 'district_id'=>$district->id]);
    Municipality::create(['name'=>'MUNICIPIO SIN RED', 'normalized_name'=>'MUNICIPIO SIN RED']);
    District::create(['name'=>'RED SIN MUNICIPIO', 'normalized_name'=>'RED SIN MUNICIPIO']);
    $this->get(route('citologia.reportes.estadistico', ['year'=>2026, 'month'=>10, 'period'=>$period, 'type'=>'rural', 'format'=>'print']))
        ->assertOk()->assertSee('RED SIN CASOS')->assertSee('MUNICIPIO SIN CASOS')
        ->assertDontSee('MUNICIPIO SIN RED')->assertDontSee('RED SIN MUNICIPIO')
        ->assertSee('POTOSI RURAL')->assertSee('SAN P. DE MACHA')->assertSee('rowspan=', false)
        ->assertViewHas('rows', fn ($rows) => count($rows) === 43 && collect($rows)->every(fn ($row) => array_slice($row, 2) === array_fill(0, 13, 0)));
    $this->get(route('citologia.reportes.estadistico', ['year'=>2026, 'period'=>$period, 'type'=>'rural', 'format'=>'excel']))->assertOk()->assertDownload();
})->with(['monthly', 'annual']);

test('urban statistics include urban establishments without results', function ($period) {
    $this->actingAs(User::factory()->create(['role' => 'cytology_office']));
    HealthFacility::create(['name'=>'CENTRO URBANO SIN CASOS', 'normalized_name'=>'CENTRO URBANO SIN CASOS', 'classification'=>'U']);
    HealthFacility::create(['name'=>'CENTRO EXCLUSIVO RURAL', 'normalized_name'=>'CENTRO EXCLUSIVO RURAL', 'classification'=>'R']);
    $this->get(route('citologia.reportes.estadistico', ['year'=>2026, 'period'=>$period, 'type'=>'urban', 'format'=>'print']))
        ->assertOk()->assertSee('CENTRO URBANO SIN CASOS')->assertDontSee('CENTRO EXCLUSIVO RURAL')
        ->assertViewHas('rows', fn ($rows) => count($rows) === 2 && array_slice($rows[0], 1) === array_fill(0, 13, 0));
})->with(['monthly', 'annual']);
