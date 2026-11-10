<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use App\Domain\User\Models\Profile;

final readonly class Placement
{
    public function __construct(
        public ?int $organizationId = null,
        public ?int $branchId = null,
        public ?int $departmentId = null,
        public ?int $positionId = null,
    ) {}

    public static function of(?Profile $profile): self
    {
        return new self(
            $profile?->organization_id,
            $profile?->branch_id,
            $profile?->department_id,
            $profile?->position_id,
        );
    }

    /**
     * @return array{organization_id: ?int, branch_id: ?int, department_id: ?int, position_id: ?int}
     */
    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'branch_id' => $this->branchId,
            'department_id' => $this->departmentId,
            'position_id' => $this->positionId,
        ];
    }
}
