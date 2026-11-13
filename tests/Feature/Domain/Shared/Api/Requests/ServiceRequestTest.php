<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\User\Models\User;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\Fixtures\Connectors\BillingConnector;
use Tests\Fixtures\Requests\GetStatus;

beforeEach(function (): void {
    config(['system.api.services' => ['billing' => ['base_url' => 'https://billing.test']]]);
});

test('a call about a record keeps that record on the row', function (): void {
    $user = User::factory()->create();
    Saloon::fake([MockResponse::make()]);

    new BillingConnector()->send(new GetStatus()->about($user));

    expect(ApiCall::query()->sole()->resource?->is($user))->toBeTrue();
});

test('the record of a call does not carry over to the next call of the same connector', function (): void {
    $user = User::factory()->create();
    Saloon::fake([MockResponse::make(), MockResponse::make()]);
    $connector = new BillingConnector;

    $connector->send(new GetStatus()->about($user));
    $connector->send(new GetStatus);

    expect(ApiCall::query()->oldest('id')->pluck('resource_id')->all())->toBe([$user->id, null]);
});
