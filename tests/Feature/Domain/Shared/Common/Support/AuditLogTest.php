<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Support\AuditLog;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\User\Models\User;

test('the record lookup finds records of a type by name, deleted ones too', function (): void {
    $ada = User::factory()->create(['name' => 'Ada Lovelace']);
    $adam = User::factory()->create(['name' => 'Adam Smith']);
    User::factory()->create(['name' => 'Grace Hopper']);
    $adam->delete();

    $records = AuditLog::records('user', 'ada');

    expect($records)->toBe([$ada->id => 'Ada Lovelace', $adam->id => 'Adam Smith']);
});

test('the record lookup names the picked records, deleted ones too', function (): void {
    $organization = Organization::factory()->create(['name' => 'Acme Holdings']);
    $closed = Organization::factory()->create(['name' => 'Closed Ltd']);
    $closed->delete();

    $names = AuditLog::recordNames('organization', [(string) $organization->id, (string) $closed->id]);

    expect($names)->toBe([$organization->id => 'Acme Holdings', $closed->id => 'Closed Ltd']);
});

test('records that a name does not pick out cannot be looked up', function (string $type): void {
    expect(AuditLog::nameColumn($type))->toBeNull()
        ->and(AuditLog::records($type, ''))->toBeEmpty();
})->with(['profile', 'action', 'an unknown type' => 'unknown']);

test('the causer types are the types that made changes, and none', function (): void {
    $this->actingAs(User::factory()->create());
    Organization::factory()->create();

    expect(AuditLog::causerTypes())->toBe(['user' => 'User', AuditLog::NO_CAUSER => 'None']);
});
