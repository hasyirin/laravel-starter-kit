<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;

test('viewing the sign-in log needs system.logs.sign-ins.view', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();

    expect($user->can('viewAny', SignInEntry::class))->toBeFalse();

    grantPermissions($user, 'system.logs.sign-ins.view');

    expect($user->refresh()->can('viewAny', SignInEntry::class))->toBeTrue();
});
