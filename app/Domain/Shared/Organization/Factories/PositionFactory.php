<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Factories;

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'department_id' => null,
            'name' => fake()->jobTitle(),
        ];
    }

    public function forDepartment(Department $department): static
    {
        return $this->state([
            'organization_id' => $department->organization_id,
            'department_id' => $department->id,
        ]);
    }
}
