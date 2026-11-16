<?php

use App\Models\PathologyRequest;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('system roles and permissions are synchronized', function () {
    $this->seed(UserSeeder::class);

    $administrator = User::query()->where('role', 'administrator')->firstOrFail();

    expect(Role::query()->pluck('name')->all())
        ->toContain('administrator', 'user')
        ->and($administrator->hasRole('administrator'))->toBeTrue()
        ->and($administrator->can('manage configuration'))->toBeTrue();
});

test('critical records support soft deletion', function () {
    expect(Schema::hasColumn('users', 'deleted_at'))->toBeTrue()
        ->and(Schema::hasColumn('pathology_requests', 'deleted_at'))->toBeTrue()
        ->and(Schema::hasColumn('cytology_reports', 'deleted_at'))->toBeTrue()
        ->and(in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive(PathologyRequest::class), true))
        ->toBeTrue();
});

test('authenticated pages include the global logout action', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('inicio'))
        ->assertOk()
        ->assertSee('Cerrar sesión');
});

test('dashboard adds period controls and keeps six months as its default', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('inicio'))
        ->assertOk()
        ->assertSee('Últimos 30 días')
        ->assertSee('Últimos 6 meses')
        ->assertSee('Últimos 12 meses')
        ->assertSee('Registros de los últimos seis meses');

    $this->actingAs($user)
        ->get(route('inicio', ['period' => '365']))
        ->assertOk()
        ->assertSee('Registros de los últimos doce meses');
});

test('dashboard summarizes every authorized pathology area', function () {
    $administrator = User::factory()->create(['role' => 'administrator']);
    $cytologyUser = User::factory()->create(['role' => 'cytology_office']);

    $this->actingAs($administrator)
        ->get(route('inicio'))
        ->assertOk()
        ->assertSee('Solicitudes y Resultados')
        ->assertSee('Citología')
        ->assertSee('Anatomopatología')
        ->assertSee('Estados por módulo')
        ->assertSee('Tareas pendientes de todas las áreas autorizadas');

    $response = $this->actingAs($cytologyUser)->get(route('inicio'));
    $response->assertOk()
        ->assertSee('Citología')
        ->assertDontSee('Solicitudes y Resultados')
        ->assertDontSee('Anatomopatología');
});

test('shared shell hides the quick search button without changing module links', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('inicio'))
        ->assertOk()
        ->assertDontSee('id="global-search-trigger"', false)
        ->assertSee('id="command-palette"', false)
        ->assertSee('Búsqueda rápida')
        ->assertSee(route('solicitudes.index'), false)
        ->assertSee(route('resultados.index'), false)
        ->assertSee(route('citologia.index'), false)
        ->assertSee(route('anatomopatologia.index'), false);
});

test('patient history is separated according to office access', function () {
    $patient = \App\Models\Patient::query()->create([
        'identity_document' => 'HIST-001',
        'full_name' => 'PACIENTE HISTORIAL',
        'is_active' => true,
    ]);
    $laboratoryUser = User::factory()->create(['role' => 'laboratory_office']);
    $cytologyUser = User::factory()->create(['role' => 'cytology_office']);
    $administrator = User::factory()->create(['role' => 'administrator']);

    $this->actingAs($laboratoryUser)
        ->get(route('historial.index', ['paciente' => $patient->id]))
        ->assertOk()
        ->assertSee('data-history-tab="laboratory"', false)
        ->assertDontSee('data-history-tab="cytology"', false)
        ->assertDontSee('data-history-tab="anatomopathology"', false);

    $this->actingAs($cytologyUser)
        ->get(route('historial.index', ['paciente' => $patient->id]))
        ->assertOk()
        ->assertSee('data-history-tab="cytology"', false)
        ->assertDontSee('data-history-tab="laboratory"', false)
        ->assertDontSee('data-history-tab="anatomopathology"', false);

    $this->actingAs($administrator)
        ->get(route('historial.index', ['paciente' => $patient->id]))
        ->assertOk()
        ->assertSee('data-history-tab="laboratory"', false)
        ->assertSee('data-history-tab="cytology"', false)
        ->assertSee('data-history-tab="anatomopathology"', false);
});

test('authenticated shell exposes professional navigation without hiding modules', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('inicio'))
        ->assertOk()
        ->assertDontSee('BUSCAR PACIENTE, SOLICITUD O INFORME')
        ->assertSee('Activar modo oscuro')
        ->assertSee('id="topbar-mobile-menu"', false)
        ->assertDontSee('id="sidebar-toggle"', false)
        ->assertSee('aria-current="page"', false)
        ->assertSee('Solicitudes / Muestras')
        ->assertSee('Registro de Resultados')
        ->assertSee('Informes citológicos')
        ->assertSee('Anatomopatológico');
});

test('results module keeps its professional search and management actions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('resultados.index'))
        ->assertOk()
        ->assertSee('Solicitudes recibidas')
        ->assertSee('BUSCAR POR CORRELATIVO, CI O PACIENTE')
        ->assertSee('data-datatable-professional', false)
        ->assertSee('Registro de Resultados');
});

test('each principal module renders inside the shared shell', function () {
    $user = User::factory()->create();

    foreach (['solicitudes.index', 'resultados.index', 'citologia.index', 'anatomopatologia.index'] as $route) {
        $this->actingAs($user)
            ->get(route($route))
            ->assertOk()
            ->assertSee('id="theme-toggle"', false)
            ->assertSee('Cerrar sesión');
    }
});
