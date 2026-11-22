<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Enums\AuditDirection;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\User\AuditLogDataTable;
use Illuminate\Support\Facades\Exceptions;
use Livewire\Livewire;
use Tests\Fixtures\Enums\Priority;
use Tests\Fixtures\Enums\Status;
use Tests\Fixtures\Livewire\EnumProperties;

test('a known value updates an enum property', function (): void {
    Livewire::test(EnumProperties::class)
        ->set('status', 'published')
        ->set('priority', 2)
        ->assertSet('status', Status::Published)
        ->assertSet('priority', Priority::High);
});

test('null or an empty string clears a nullable enum property', function (string $property, mixed $value): void {
    Livewire::test(EnumProperties::class)
        ->set($property, $value)
        ->assertSet($property, null);
})->with([
    'a set property' => ['status'],
    'a null property' => ['priority'],
])->with([
    'null' => [null],
    'an empty string' => [''],
]);

test('a bad enum value expires the page in production and is not reported', function (string $property, mixed $value): void {
    config(['app.debug' => false]);
    $component = Livewire::test(EnumProperties::class);
    Exceptions::fake();

    $response = sendLivewireUpdate($component, [$property => $value]);

    $response->assertStatus(419);
    Exceptions::assertNothingReported();
})->with([
    'an unknown string for a set property' => ['status', 'archived'],
    'an int for a string-backed property' => ['status', 7],
    'an unknown int for a null property' => ['priority', 9],
    'an unknown numeric string for a null property' => ['priority', '9'],
    'a non-numeric string for an int-backed property' => ['priority', 'abc'],
    'an array for a set property' => ['status', ['draft']],
]);

test('a bad enum value throws its error in debug mode', function (string $property, string $value, string $error): void {
    config(['app.debug' => true]);

    $response = sendLivewireUpdate(Livewire::test(EnumProperties::class), [$property => $value]);

    $response->assertServerError();

    expect($response->exception)->toBeInstanceOf($error);
})->with([
    'an unknown value' => ['status', 'archived', ValueError::class],
    'a value of the wrong type' => ['priority', 'abc', TypeError::class],
]);

test('an unknown direction for the audit log of a user expires the page', function (): void {
    config(['app.debug' => false]);
    $this->seed(PermissionSeeder::class);
    $actor = User::factory()->create();
    grantPermissions($actor, 'internal.access', 'internal.controls.users.view', 'internal.controls.users.view-audit-log');
    $this->actingAs($actor);
    $table = Livewire::withoutLazyLoading()->test(AuditLogDataTable::class, ['user' => User::factory()->create(), 'direction' => AuditDirection::On]);

    $response = sendLivewireUpdate($table, ['direction' => 'sideways']);

    $response->assertStatus(419);
});
