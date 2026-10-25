<?php

declare(strict_types=1);

use App\Domain\Shared\Documentation\Support\ReferenceChecker;
use App\Domain\User\Models\User;

test('code names that exist pass the check', function (string $reference): void {
    expect(new ReferenceChecker()->problem($reference))->toBeNull();
})->with([
    'a file' => 'routes/web.php',
    'a folder' => 'app/Domain/',
    'a file name' => 'guest.php',
    'a class' => 'PermissionSeeder',
    'a full class name' => User::class,
    'a method' => 'LoginResponse::highestAreaUrl()',
    'a facade method' => 'Gate::before',
    'a permission' => 'admin.horizon.view',
    'a permission pattern' => 'admin.controls.roles.*',
    'a permission in the can middleware' => 'can:admin.access',
    'a route name' => 'internal.settings',
    'a config key' => 'system.permissions',
    'a table column' => 'roles.is_system',
    'an environment variable' => 'HORIZON_PATH',
]);

test('code names that do not exist fail the check', function (string $reference, string $problem): void {
    expect(new ReferenceChecker()->problem($reference))->toBe($problem);
})->with([
    'a file' => ['routes/missing.php', 'is not a file or folder'],
    'a file name' => ['missing.php', 'is not a file'],
    'a class' => ['MissingSeeder', 'is not a class'],
    'a method' => ['LoginResponse::missing()', 'names a member that LoginResponse does not have'],
    'a method of a missing class' => ['Missing::method()', 'names a class that does not exist'],
    'a dotted name' => ['admin.missing', 'is not a permission, route name, config key, or table column'],
    'an environment variable' => ['MISSING_VARIABLE', 'is not read by a config file or listed in .env.example'],
]);

test('words that are not code names are not checked', function (string $reference): void {
    expect(new ReferenceChecker()->problem($reference))->toBeNull();
})->with([
    'a command' => 'php artisan optimize',
    'a URL' => '/admin/horizon',
    'a name prefix' => 'internal.',
    'a version' => '1.2.3',
    'a single word' => 'verified',
    'a variable' => '$prefix',
]);
