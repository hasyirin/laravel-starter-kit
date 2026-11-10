<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use Livewire\Attributes\Computed;

trait ListsCarbonCopyTargets
{
    /**
     * @return array<string, string>
     */
    #[Computed]
    public function organizations(): array
    {
        /** @var array<string, string> $organizations */
        $organizations = Organization::query()->orderBy('name')->pluck('name', 'id')->all();

        return $organizations;
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function departments(): array
    {
        if ($this->acrossOrganizations()) {
            /** @var array<string, string> $all */
            $all = Department::query()
                ->with('organization')
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (Department $department): array => [$department->id => "{$department->organization?->name}: {$department->name}"])
                ->all();

            return $all;
        }

        /** @var array<string, string> $departments */
        $departments = Department::query()
            ->where('organization_id', auth()->user()?->profile?->organization_id)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        return $departments;
    }

    protected function acrossOrganizations(): bool
    {
        return false;
    }
}
