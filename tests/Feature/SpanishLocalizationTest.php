<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('application dates validation and navigation use Spanish', function () {
    expect(app()->getLocale())->toBe('es');
    expect(Carbon::parse('2026-10-14')->translatedFormat('d \d\e F \d\e Y'))->toBe('14 de octubre de 2026');
    expect(__('pagination.next'))->toContain('Siguiente');
    expect(__('workflow.validated'))->toBe('Validado');
    $errors = Validator::make([], ['patient_age' => 'required'])->errors();
    expect($errors->first('patient_age'))->toBe('El campo edad es obligatorio.');
    $errors = Validator::make(['patient_age' => 150], ['patient_age' => 'integer|max:130'])->errors();
    expect($errors->first('patient_age'))->toContain('edad')->not->toContain('The ');
    $this->actingAs(User::factory()->create())->get(route('citologia.index'))->assertOk()->assertSee('Octubre');
});
