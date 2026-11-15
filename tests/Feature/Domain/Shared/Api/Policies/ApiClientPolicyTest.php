<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->apiClient = ApiClient::factory()->create();
});

test('view any needs the view-any permission', function (): void {
    expect($this->user->can('viewAny', ApiClient::class))->toBeFalse();

    grantPermissions($this->user, 'system.api-clients.view-any');

    expect($this->user->can('viewAny', ApiClient::class))->toBeTrue();
});

test('view needs the view-any or view permission', function (string $permission): void {
    expect($this->user->can('view', $this->apiClient))->toBeFalse();

    grantPermissions($this->user, $permission);

    expect($this->user->can('view', $this->apiClient))->toBeTrue();
})->with(['system.api-clients.view-any', 'system.api-clients.view']);

test('viewing the audit log needs view-audit-log', function (): void {
    grantPermissions($this->user, 'system.api-clients.view');

    expect($this->user->can('view', [$this->apiClient, 'audit-log']))->toBeFalse();

    grantPermissions($this->user, 'system.api-clients.view-audit-log');

    expect($this->user->can('view', [$this->apiClient, 'audit-log']))->toBeTrue();
});

test('create needs the create permission', function (): void {
    expect($this->user->can('create', ApiClient::class))->toBeFalse();

    grantPermissions($this->user, 'system.api-clients.create');

    expect($this->user->can('create', ApiClient::class))->toBeTrue();
});

test('update, and each token action, needs the update permission', function (): void {
    expect($this->user->can('update', $this->apiClient))->toBeFalse();

    grantPermissions($this->user, 'system.api-clients.update');

    expect($this->user->can('update', $this->apiClient))->toBeTrue();
});

test('delete needs the delete permission', function (): void {
    expect($this->user->can('delete', $this->apiClient))->toBeFalse();

    grantPermissions($this->user, 'system.api-clients.delete');

    expect($this->user->can('delete', $this->apiClient))->toBeTrue();
});
