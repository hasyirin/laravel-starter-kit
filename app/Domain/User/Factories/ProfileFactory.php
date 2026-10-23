<?php

declare(strict_types=1);

namespace App\Domain\User\Factories;

use App\Domain\Shared\Permission\Models\Permission;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'organization_id' => null,
            'department_id' => null,
            'position_id' => null,
            'branch_id' => null,
            'user_id' => User::factory(),
        ];
    }

    public function withRoles(Role|string ...$roles): static
    {
        return $this->afterMaking(fn (Profile $profile): Profile => $profile->assignRole(...$roles));
    }

    public function withPermissions(Permission|string ...$permissions): static
    {
        return $this->afterMaking(fn (Profile $profile): Profile => $profile->givePermissionTo(...$permissions));
    }
}
