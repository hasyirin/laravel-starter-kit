<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Actions;

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Common\Concerns\Actions\Actionable;

class DeleteApiClient
{
    use Actionable;

    public function execute(ApiClient $apiClient): void
    {
        $apiClient->getConnection()->transaction(function () use ($apiClient): void {
            $apiClient->tokens()->active()->get()->each(fn (ApiToken $apiToken) => RevokeApiToken::make()->execute($apiToken));
            $apiClient->delete();
        });
    }
}
