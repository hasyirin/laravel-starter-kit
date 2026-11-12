<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Actions;

use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Common\Concerns\Actions\Actionable;

class RevokeApiToken
{
    use Actionable;

    public function execute(ApiToken $apiToken): void
    {
        if ($apiToken->revoked) {
            return;
        }

        $apiToken->update(['revoked_at' => now()]);
    }
}
