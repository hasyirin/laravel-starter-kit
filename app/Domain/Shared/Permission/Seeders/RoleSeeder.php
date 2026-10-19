<?php

namespace App\Domain\Shared\Permission\Seeders;

use App\Domain\Shared\Common\Seeders\Seeder;
use App\Domain\Shared\Permission\Models\Role;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

/**
 * @extends Seeder<string, array{permissions: list<string>, except: list<string>}>
 */
class RoleSeeder extends Seeder
{
    /**
     * @var Collection<int, string>
     */
    protected Collection $permissions;

    protected function before(): void
    {
        $this->permissions = collect(config('system.permissions'))->flatten()->map(fn (mixed $permission): string => (string) $permission);
    }

    protected function each(string|int $key, mixed $data): void
    {
        $role = Role::query()->firstOrCreate(['name' => $key, 'guard_name' => 'web']);
        $role->forceFill(['is_system' => true])->save();

        $role->givePermissionTo(
            $this->permissions->filter(fn (string $permission): bool => str($permission)->is($data['permissions']) && ! str($permission)->is($data['except'])),
        );
    }

    protected function after(): void
    {
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function data(): array
    {
        return [
            config('system.roles.admin') => [
                'permissions' => [
                    'admin.*',
                ],
                'except' => [],
            ],
        ];
    }
}
