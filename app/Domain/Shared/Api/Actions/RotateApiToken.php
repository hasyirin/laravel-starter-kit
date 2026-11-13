<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Actions;

use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Api\Support\IssuedApiToken;
use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use LogicException;

class RotateApiToken
{
    use Actionable;

    public function execute(ApiToken $apiToken): IssuedApiToken
    {
        $apiClient = $apiToken->apiClient;

        throw_if($apiClient === null || $apiToken->revoked || $apiToken->expired, LogicException::class, 'Only an active API token of an API client can be rotated.');

        return $apiToken->getConnection()->transaction(function () use ($apiToken, $apiClient): IssuedApiToken {
            /** @var list<string> $declared */
            $declared = config()->array('system.api.scopes');

            $scopes = collect($apiToken->scopes)->intersect($declared)->values()->all();
            $issued = IssueApiToken::make()->execute($apiClient, $scopes, neverExpires: $apiToken->expires_at === null);

            $overlapEnd = now()->addDays(config()->integer('system.api.rotation_overlap_days'));

            if ($apiToken->expires_at === null || $apiToken->expires_at->gt($overlapEnd)) {
                $apiToken->update(['expires_at' => $overlapEnd]);
            }

            return $issued;
        });
    }
}
