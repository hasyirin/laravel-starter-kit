<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Factories;

use App\Domain\Shared\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'code' => str(fake()->unique()->lexify('??????'))->upper()->value(),
            'name' => fake()->company(),
        ];
    }
}
