<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Seeders;

use App\Domain\Shared\Common\Seeders\Seeder;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;

/**
 * @phpstan-type PositionData array{name: string}
 * @phpstan-type BranchData array{code: string, name: string, owner?: bool}
 * @phpstan-type DepartmentData array{code: string, name: string, owner?: bool, positions?: list<PositionData>}
 * @phpstan-type OrganizationData array{code: string, name: string, owner?: bool, branches?: list<BranchData>, departments?: list<DepartmentData>, positions?: list<PositionData>}
 *
 * @extends Seeder<int, OrganizationData>
 */
class OrganizationSeeder extends Seeder
{
    protected function before(): void
    {
        $this->forgetOwners();
    }

    protected function each(string|int $key, mixed $data): void
    {
        $organization = ($data['owner'] ?? false)
            ? Organization::owner()
            : Organization::query()->firstOrCreate(['code' => $data['code']], ['name' => $data['name']]);

        foreach ($data['branches'] ?? [] as $branch) {
            if ($branch['owner'] ?? false) {
                Branch::owner();
            } else {
                $organization->branches()->firstOrCreate(['code' => $branch['code']], ['name' => $branch['name']]);
            }
        }

        foreach ($data['departments'] ?? [] as $department) {
            $model = ($department['owner'] ?? false)
                ? Department::owner()
                : $organization->departments()->firstOrCreate(['code' => $department['code']], ['name' => $department['name']]);

            foreach ($department['positions'] ?? [] as $position) {
                $organization->positions()->firstOrCreate(['department_id' => $model->id, 'name' => $position['name']]);
            }
        }

        foreach ($data['positions'] ?? [] as $position) {
            $organization->positions()->firstOrCreate(['department_id' => null, 'name' => $position['name']]);
        }
    }

    protected function after(): void
    {
        $this->forgetOwners();
    }

    private function forgetOwners(): void
    {
        Organization::forgetOwner();
        Department::forgetOwner();
        Branch::forgetOwner();
    }

    public function data(): array
    {
        return [
            [
                'code' => config('system.owner.organization.code'),
                'name' => config('system.owner.organization.name'),
                'owner' => true,
                'branches' => [
                    ['code' => config('system.owner.branch.code'), 'name' => config('system.owner.branch.name'), 'owner' => true],
                ],
                'departments' => [
                    [
                        'code' => config('system.owner.department.code'),
                        'name' => config('system.owner.department.name'),
                        'owner' => true,
                        'positions' => [
                            ['name' => 'Manager'],
                        ],
                    ],
                ],
                'positions' => [
                    ['name' => 'Chief Executive Officer'],
                ],
            ],
        ];
    }
}
