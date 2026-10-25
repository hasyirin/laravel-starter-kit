<?php

declare(strict_types=1);

use App\Domain\Shared\Documentation\Sections\PermissionMatrix;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use Tests\Fixtures\Seeders\AuditorRoleSeeder;

test('the matrix marks the permissions of each seeded role', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class, AuditorRoleSeeder::class]);

    expect(new PermissionMatrix()->render([]))
        ->toMatch('/^\| Permission +\| user\/roles\.auditor +\| Super Administrator +\|$/m')
        ->toMatch('/^\| `admin\.access` +\| ✓ +\| ✓ +\|$/m')
        ->toMatch('/^\| `admin\.controls\.roles\.delete` +\| +\| ✓ +\|$/m')
        ->toMatch('/^\| `internal\.access` +\| +\| ✓ +\|$/m')
        ->toEndWith('A role with `bypass-authorization` passes every check.');
});

test('the matrix has no bypass note when no role bypasses the checks', function (): void {
    $this->seed([PermissionSeeder::class, AuditorRoleSeeder::class]);

    expect(new PermissionMatrix()->render([]))->not->toContain('passes every check');
});
