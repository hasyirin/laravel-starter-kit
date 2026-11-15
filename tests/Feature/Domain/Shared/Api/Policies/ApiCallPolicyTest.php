<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

test('viewing the API log needs system.logs.api.view', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $apiCall = ApiCall::factory()->create();

    expect($user->can('viewAny', ApiCall::class))->toBeFalse()
        ->and($user->can('view', $apiCall))->toBeFalse();

    grantPermissions($user, 'system.logs.api.view');

    expect($user->refresh()->can('viewAny', ApiCall::class))->toBeTrue()
        ->and($user->can('view', $apiCall))->toBeTrue();
});
