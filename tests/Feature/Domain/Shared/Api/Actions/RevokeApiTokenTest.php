<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Actions\RevokeApiToken;
use App\Domain\Shared\Api\Models\ApiToken;

test('revoking sets the revocation time and keeps the row', function (): void {
    $this->freezeSecond();
    $apiToken = ApiToken::factory()->create();

    RevokeApiToken::make()->execute($apiToken);

    expect($apiToken->fresh())->not->toBeNull()
        ->revoked_at->equalTo(now())->toBeTrue()
        ->trashed()->toBeFalse();
});

test('revoking a revoked token keeps the first revocation time', function (): void {
    $apiToken = ApiToken::factory()->revoked()->create(['revoked_at' => now()->subDay()]);
    $first = $apiToken->revoked_at;

    RevokeApiToken::make()->execute($apiToken);

    expect($apiToken->fresh()?->revoked_at?->equalTo($first))->toBeTrue();
});
