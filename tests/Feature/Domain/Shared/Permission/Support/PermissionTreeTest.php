<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Support\PermissionTree;

test('a resource holds its abilities as permissions', function (): void {
    $tree = PermissionTree::build(['internal.controls.users.view', 'internal.controls.users.update-roles']);

    expect($tree['children'][0]['children'][0]['children'][0])->toBe([
        'key' => 'internal.controls.users',
        'label' => 'Users',
        'kind' => 'resource',
        'permissions' => [
            ['name' => 'internal.controls.users.view', 'label' => 'View'],
            ['name' => 'internal.controls.users.update-roles', 'label' => 'Update roles'],
        ],
        'children' => [],
    ]);
});

test('a permission directly under a group is listed on the group', function (): void {
    $system = PermissionTree::build(['system.access', 'system.horizon.view'])['children'][0];

    expect($system['kind'])->toBe('group')
        ->and($system['permissions'])->toBe([['name' => 'system.access', 'label' => 'Access']])
        ->and($system['children'][0]['key'])->toBe('system.horizon')
        ->and($system['children'][0]['kind'])->toBe('resource');
});

test('a one-segment permission is listed at the root', function (): void {
    $tree = PermissionTree::build(['bypass-authorization', 'internal.access']);

    expect($tree['permissions'])->toBe([['name' => 'bypass-authorization', 'label' => 'Bypass authorization']])
        ->and(collect($tree['children'])->pluck('key')->all())->toBe(['internal']);
});

test('a missing label falls back to the headline', function (): void {
    $reports = PermissionTree::build(['monthly-reports.export-csv'])['children'][0];

    expect($reports['label'])->toBe('Monthly reports')
        ->and($reports['permissions'][0]['label'])->toBe('Export csv');
});

test('a node with only an access permission is a group', function (): void {
    [$internal, $system] = PermissionTree::build(['internal.access', 'system.horizon.view'])['children'];

    expect($internal['kind'])->toBe('group')
        ->and($internal['permissions'])->toBe([['name' => 'internal.access', 'label' => 'Access']])
        ->and($system['children'][0]['kind'])->toBe('resource');
});
