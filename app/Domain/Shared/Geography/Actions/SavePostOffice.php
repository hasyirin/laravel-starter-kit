<?php

declare(strict_types=1);

namespace App\Domain\Shared\Geography\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Support\UniqueKey;

class SavePostOffice
{
    use Actionable;

    public function execute(State $state, PostOffice $postOffice, string $name): PostOffice
    {
        $postOffice->state()->associate($state);
        $postOffice->fill(['name' => $name]);

        return UniqueKey::save($postOffice, ['state_id', 'name']);
    }
}
