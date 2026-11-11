<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support;

use App\Domain\Shared\Letter\Enums\SignerRequirement;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PermitSignedLetter extends PermitLetter
{
    public function signer(): SignerRequirement
    {
        return SignerRequirement::Required;
    }

    public function offersComputerSigned(): bool
    {
        return true;
    }

    /**
     * @param  Builder<User>  $users
     * @return Builder<User>
     */
    public function signers(Builder $users, User $printer): Builder
    {
        return $users->where('name', '!=', 'Excluded Signer');
    }
}
