<?php

use App\Models\Area;
use App\Models\District;
use App\Models\Municipality;
use App\Models\RegionalSecretariat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('an administrator can create the location catalogs without leaving requests', function () {
    $admin = User::factory()->create(['role' => 'administrator']);
    $regional = RegionalSecretariat::query()->create([
        'name' => 'REGIONAL PRUEBA', 'normalized_name' => 'REGIONAL PRUEBA', 'is_active' => true,
    ]);

    $municipalityId = $this->actingAs($admin)->postJson(route('solicitudes.catalogos.store'), [
        'type' => 'municipality', 'name' => 'Municipio nuevo', 'regional_secretariat_id' => $regional->id,
    ])->assertCreated()->assertJsonPath('option.name', 'Municipio nuevo')->json('option.id');

    $districtId = $this->postJson(route('solicitudes.catalogos.store'), [
        'type' => 'district', 'name' => 'Distrito nuevo', 'regional_secretariat_id' => $regional->id,
        'municipality_id' => $municipalityId,
    ])->assertCreated()->json('option.id');

    $areaId = $this->postJson(route('solicitudes.catalogos.store'), [
        'type' => 'area', 'name' => 'Area nueva', 'district_id' => $districtId,
    ])->assertCreated()->json('option.id');

    $sectorId = $this->postJson(route('solicitudes.catalogos.store'), [
        'type' => 'sector', 'name' => 'Sector nuevo', 'area_id' => $areaId,
    ])->assertCreated()->json('option.id');

    $this->postJson(route('solicitudes.catalogos.store'), [
        'type' => 'facility', 'name' => 'Establecimiento nuevo',
        'regional_secretariat_id' => $regional->id, 'municipality_id' => $municipalityId,
        'district_id' => $districtId, 'area_id' => $areaId, 'sector_id' => $sectorId,
        'classification' => 'R',
    ])->assertCreated();

    expect(Municipality::find($municipalityId)?->is_active)->toBeTrue()
        ->and(District::find($districtId)?->municipality_id)->toBe($municipalityId)
        ->and(Area::find($areaId)?->district_id)->toBe($districtId);
});

test('non administrators cannot create quick catalog options', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)->postJson(route('solicitudes.catalogos.store'), [
        'type' => 'district', 'name' => 'NO AUTORIZADO',
    ])->assertForbidden();
});
