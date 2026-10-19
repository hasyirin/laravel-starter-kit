<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Factories;

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => str(fake()->unique()->lexify('??????'))->upper()->value(),
            'name' => str(fake()->word())->title()->value(),
        ];
    }
}
