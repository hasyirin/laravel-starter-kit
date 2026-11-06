<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Geography\Seeders\GeographySeeder;
use App\Domain\Shared\Organization\Seeders\OrganizationSeeder;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use App\Domain\User\Seeders\UserSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            GeographySeeder::class,
            OrganizationSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }
}
