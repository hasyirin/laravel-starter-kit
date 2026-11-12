<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Common\Models\AuditEntry;

beforeEach(function (): void {
    config(['system.api.token_prefix' => 'kit']);
});

test('a token is found by its plain value', function (): void {
    $apiToken = ApiToken::factory()->withPlainToken('kit_abcdefghijABCDEFGHIJ0123456789klmnopqrst2ywciD')->create();

    expect(ApiToken::findByToken('kit_abcdefghijABCDEFGHIJ0123456789klmnopqrst2ywciD')?->is($apiToken))->toBeTrue();
});

test('a token with the wrong form or an unknown value is not found', function (string $token): void {
    ApiToken::factory()->withPlainToken('kit_abcdefghijABCDEFGHIJ0123456789klmnopqrst2ywciD')->create();

    expect(ApiToken::findByToken($token))->toBeNull();
})->with([
    'a wrong checksum' => ['kit_abcdefghijABCDEFGHIJ0123456789klmnopqrst2ywciE'],
    'a valid token that the app did not issue' => ['kit_zyxwvutsrqZYXWVUTSRQ9876543210ponmlkjihg46AexM'],
]);

test('a token is expired after its expiry time and revoked after its revocation', function (): void {
    expect(ApiToken::factory()->make())->expired->toBeFalse()->revoked->toBeFalse()
        ->and(ApiToken::factory()->neverExpires()->make())->expired->toBeFalse()
        ->and(ApiToken::factory()->expired()->make())->expired->toBeTrue()
        ->and(ApiToken::factory()->revoked()->make())->revoked->toBeTrue();
});

test('only a token that is neither revoked nor expired is active', function (): void {
    $active = ApiToken::factory()->create();
    $neverExpires = ApiToken::factory()->neverExpires()->create();
    ApiToken::factory()->expired()->create();
    ApiToken::factory()->revoked()->create();

    expect(ApiToken::query()->active()->pluck('id')->sort()->values()->all())->toBe([$active->id, $neverExpires->id]);
});

test('the time of the last use is written at most one time per minute', function (): void {
    $this->freezeSecond();
    $apiToken = ApiToken::factory()->create();

    $apiToken->recordUse();

    $firstUse = $apiToken->fresh()?->last_used_at;
    $this->travel(59)->seconds();
    $apiToken->recordUse();

    expect($apiToken->fresh()?->last_used_at?->equalTo($firstUse))->toBeTrue();

    $this->travel(1)->seconds();
    $apiToken->recordUse();

    expect($apiToken->fresh()?->last_used_at?->equalTo(now()))->toBeTrue();
});

test('the last use stays out of the audit log', function (): void {
    $apiToken = ApiToken::factory()->create();

    $apiToken->recordUse();

    expect(AuditEntry::query()->where('subject_type', 'api_token')->where('event', 'updated')->exists())->toBeFalse();
});

test('the hash of a token stays out of the audit log', function (): void {
    ApiToken::factory()->for(ApiClient::factory())->create();

    $entry = AuditEntry::query()->where('subject_type', 'api_token')->where('event', 'created')->sole();

    expect($entry->attribute_changes?->get('attributes'))->not->toHaveKey('token_hash')
        ->toHaveKey('last_four');
});
