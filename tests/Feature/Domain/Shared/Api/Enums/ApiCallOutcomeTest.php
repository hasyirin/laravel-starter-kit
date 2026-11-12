<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Enums\ApiCallDirection;
use App\Domain\Shared\Api\Enums\ApiCallOutcome;

test('a status code gives the outcome of a call that the app did not reject before it ran', function (int $status, ApiCallOutcome $outcome): void {
    expect(ApiCallOutcome::fromStatus($status))->toBe($outcome);
})->with([
    'a success' => [200, ApiCallOutcome::Ok],
    'no content' => [204, ApiCallOutcome::Ok],
    'a redirect' => [302, ApiCallOutcome::Ok],
    'a validation error' => [422, ApiCallOutcome::ValidationFailed],
    'too many requests' => [429, ApiCallOutcome::RateLimited],
    'not found' => [404, ApiCallOutcome::ClientError],
    'unauthorized' => [401, ApiCallOutcome::ClientError],
    'a server error' => [500, ApiCallOutcome::ServerError],
    'a bad gateway' => [502, ApiCallOutcome::ServerError],
]);

test('every outcome and direction has a label', function (ApiCallOutcome|ApiCallDirection $case): void {
    expect($case->label())->not->toStartWith('api/');
})->with([...ApiCallOutcome::cases(), ...ApiCallDirection::cases()]);
