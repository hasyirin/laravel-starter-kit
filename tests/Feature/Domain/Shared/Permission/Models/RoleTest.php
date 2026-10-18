<?php

use App\Domain\Shared\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;

test('a new role is not internal', function (): void {
    expect(Role::query()->create(['name' => 'editor'])->fresh()?->is_internal)->toBeFalse();
});

test('the label of an internal role is translated', function (): void {
    $role = Role::query()->create(['name' => 'admin', 'is_internal' => true]);

    expect($role->label)->toBe('Administrator');
});

test('the label of a role that is not internal is its name', function (): void {
    expect(Role::query()->create(['name' => 'editor'])->label)->toBe('editor');
});

test('role changes are recorded in the activity log', function (): void {
    $role = Role::query()->create(['name' => 'editor']);

    $role->update(['name' => 'author']);

    $activity = Activity::query()->where('event', 'updated')->sole();

    expect($activity->subject_type)->toBe('role')
        ->and($activity->attribute_changes?->get('attributes'))->toBe(['name' => 'author']);
});
