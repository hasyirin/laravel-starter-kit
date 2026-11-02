<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Laravel\Passkeys\Actions\DeletePasskey;

class RevokeUserPasskey
{
    use Actionable;

    public function __construct(private readonly DeletePasskey $deletePasskey) {}

    /**
     * @throws AuthorizationException
     */
    public function execute(User $actor, User $user, int $passkeyId): void
    {
        throw_if($actor->is($user), AuthorizationException::class);

        ($this->deletePasskey)($user, $user->passkeys()->findOrFail($passkeyId));
    }
}
