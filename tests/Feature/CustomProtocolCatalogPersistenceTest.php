<?php

use App\Models\CapProtocolField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('custom protocol titles persist immediately and remain available in a new report', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));
    $id = $this->postJson(route('anatomopatologia.catalogos.store'), ['type' => 'protocol', 'name' => ' Mama '])
        ->assertCreated()->assertJsonPath('option.name', 'MAMA')->json('option.id');
    $this->get(route('anatomopatologia.index'))->assertOk()->assertSee('>MAMA</option>', false);
    $this->postJson(route('anatomopatologia.catalogos.store'), ['type' => 'protocol', 'name' => 'mama'])
        ->assertOk()->assertJsonPath('option.id', $id);
    expect(CapProtocolField::where('normalized_name', 'MAMA')->count())->toBe(1);
    CapProtocolField::findOrFail($id)->update(['is_active' => false]);
    $this->postJson(route('anatomopatologia.catalogos.store'), ['type' => 'protocol', 'name' => 'Mama'])
        ->assertUnprocessable()->assertJsonValidationErrors('name');
    expect(CapProtocolField::findOrFail($id)->is_active)->toBeFalse();
})->with(['administrator', 'anatomopathology_office']);

test('protocol creation validates titles and respects module permissions', function () {
    $this->actingAs(User::factory()->create(['role' => 'anatomopathology_office']));
    foreach ([' ', str_repeat('a', 181)] as $name) {
        $this->postJson(route('anatomopatologia.catalogos.store'), ['type' => 'protocol', 'name' => $name])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
    }
    $this->actingAs(User::factory()->create(['role' => 'reception']))
        ->postJson(route('anatomopatologia.catalogos.store'), ['type' => 'protocol', 'name' => 'MAMA'])
        ->assertForbidden();
});
