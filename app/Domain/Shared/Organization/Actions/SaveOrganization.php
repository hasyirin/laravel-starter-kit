<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Organization\Models\Organization;
use LogicException;

class SaveOrganization
{
    use Actionable;

    public function execute(Organization $organization, string $code, string $name): Organization
    {
        throw_if($organization->is_owner, LogicException::class, 'The owner organization cannot be changed here.');

        $organization->fill(['code' => $code, 'name' => $name])->save();

        return $organization;
    }
}
