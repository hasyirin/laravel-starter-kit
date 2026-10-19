<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Factories;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => str(fake()->unique()->lexify('??????'))->upper()->value(),
            'name' => fake()->city(),
        ];
    }
}
