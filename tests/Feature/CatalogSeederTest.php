<?php

use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('catalog seeders can be repeated without duplicates or overwriting local changes', function () {
    $tables = ['regional_secretariats', 'municipalities', 'districts', 'areas', 'sectors', 'health_facilities', 'hospital_services', 'physicians', 'pathology_types', 'cytology_diagnoses', 'cytology_catalog_options'];
    $this->seed(CatalogSeeder::class);
    $counts = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);
    foreach ($counts as $count) expect($count)->toBeGreaterThan(0);
    $facility = DB::table('health_facilities')->first();
    DB::table('health_facilities')->where('id', $facility->id)->update(['is_active' => false]);
    $service = DB::table('hospital_services')->first();
    DB::table('hospital_services')->where('id', $service->id)->update(['name' => 'SERVICIO EDITADO', 'is_active' => false]);
    $this->seed(CatalogSeeder::class);
    foreach ($counts as $table => $count) expect(DB::table($table)->count())->toBe($count);
    $this->assertDatabaseHas('health_facilities', ['id' => $facility->id, 'is_active' => false]);
    $this->assertDatabaseHas('hospital_services', ['id' => $service->id, 'name' => 'SERVICIO EDITADO', 'is_active' => false]);
    $this->assertDatabaseCount('patients', 0);
    $this->assertDatabaseCount('pathology_requests', 0);
    $this->assertDatabaseCount('pathology_results', 0);
});

test('user seeding preserves existing passwords and office roles', function () {
    config(['initial_admin.username' => 'admin-test', 'initial_admin.password' => 'initial-test-password']);
    $this->seed(UserSeeder::class);
    $admin = User::where('username', 'admin-test')->firstOrFail();
    $admin->update(['password' => Hash::make('changed-test-password')]);
    $office = User::factory()->create();
    $office->syncRoles('cytology_office');
    $this->seed(UserSeeder::class);
    expect(Hash::check('changed-test-password', $admin->fresh()->password))->toBeTrue();
    expect($office->fresh()->hasRole('cytology_office'))->toBeTrue();
});
