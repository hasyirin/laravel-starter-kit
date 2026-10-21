<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Seeders;

use App\Domain\Shared\Common\Seeders\Seeder;
use App\Domain\Shared\Permission\Models\Permission;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

/**
 * @extends Seeder<int, string>
 */
class PermissionSeeder extends Seeder
{
    protected function before(): void
    {
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function each(string|int $key, mixed $data): void
    {
        Permission::query()->firstOrCreate(['name' => $data]);
    }

    protected function after(): void
    {
        Permission::query()->whereNotIn('name', $this->items)->delete();

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return Collection<int, string>
     */
    public function data(): Collection
    {
        return collect(config('system.permissions'))->flatten()->map(fn (mixed $permission): string => (string) $permission);
    }
}
