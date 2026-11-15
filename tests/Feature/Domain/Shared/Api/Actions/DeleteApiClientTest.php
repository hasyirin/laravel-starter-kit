<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Actions\DeleteApiClient;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;

test('deleting an API client soft deletes it and revokes its active tokens', function (): void {
    $this->freezeSecond();
    $apiClient = ApiClient::factory()->create();
    $active = ApiToken::factory()->for($apiClient)->create();
    $revoked = ApiToken::factory()->for($apiClient)->revoked()->create(['revoked_at' => now()->subDay()]);

    DeleteApiClient::make()->execute($apiClient);

    expect($apiClient->fresh()?->trashed())->toBeTrue()
        ->and($active->fresh()?->revoked_at?->equalTo(now()))->toBeTrue()
        ->and($revoked->fresh()?->revoked_at?->equalTo(now()->subDay()))->toBeTrue();
});
