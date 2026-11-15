<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Common\Models\AuditEntry;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\System\ApiClient\ApiTokenModal;
use Dom\HTMLDocument;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    config(['system.api.token_prefix' => 'kit', 'system.api.scopes' => ['orders.read', 'orders.write']]);
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'system.access', 'system.api-clients.view', 'system.api-clients.update');
    $this->actingAs($this->actor);
    $this->apiClient = ApiClient::factory()->create();
});

function apiTokenModal(ApiClient $apiClient): Testable
{
    return Livewire::test(ApiTokenModal::class, ['apiClient' => $apiClient]);
}

/**
 * @return list<string>
 */
function plainTokensIn(string $text): array
{
    preg_match_all('/kit_[0-9A-Za-z]{46}/', $text, $matches);

    return $matches[0];
}

test('issuing an API token needs update', function (): void {
    revokePermissions($this->actor, 'system.api-clients.update');

    apiTokenModal($this->apiClient)
        ->call('show')
        ->assertForbidden();
});

test('issuing shows the plain API token one time with the warning that it cannot be seen again', function (): void {
    $modal = apiTokenModal($this->apiClient)
        ->call('show')
        ->set('form.scopes', ['orders.read'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee(__('api/api-tokens.issued_warning'))
        ->assertDispatched(ApiTokenModal::event());

    $tokens = plainTokensIn($modal->html());
    $apiToken = ApiToken::findByToken($tokens[0] ?? '');

    expect($tokens)->toHaveCount(1)
        ->and($apiToken?->api_client_id)->toBe($this->apiClient->id)
        ->and($apiToken?->scopes)->toBe(['orders.read']);
});

test('the plain API token stays out of the state of the modal and is gone after the modal closes', function (): void {
    $modal = apiTokenModal($this->apiClient)
        ->call('show')
        ->call('save');

    $token = plainTokensIn($modal->html())[0];

    expect(json_encode($modal->snapshot))->not->toContain($token);

    $modal->set('showing', false);

    expect($modal->html())->not->toContain($token)
        ->and(json_encode($modal->snapshot))->not->toContain($token);
});

test('the plain API token and its hash stay out of the audit log', function (): void {
    $modal = apiTokenModal($this->apiClient)
        ->call('show')
        ->call('save');

    $token = plainTokensIn($modal->html())[0];
    $audit = AuditEntry::query()->get()->toJson();

    expect($audit)->not->toContain($token)
        ->not->toContain(hash('sha256', $token));
});

test('an API token expires after the default lifetime unless it never expires', function (): void {
    $this->freezeSecond();
    config(['system.api.token_lifetime_days' => 365]);

    apiTokenModal($this->apiClient)->call('show')->call('save');
    apiTokenModal($this->apiClient)->call('show')->set('form.never_expires', true)->call('save');

    expect($this->apiClient->tokens()->oldest('id')->pluck('expires_at')->map(fn (?DateTimeInterface $expiresAt): ?string => $expiresAt?->format('Y-m-d H:i:s'))->all())
        ->toBe([now()->addDays(365)->format('Y-m-d H:i:s'), null]);
});

test('the modal lists the scopes that the app declares', function (): void {
    apiTokenModal($this->apiClient)
        ->call('show')
        ->assertSee(['orders.read', 'orders.write'])
        ->assertDontSee(__('api/api-tokens.no_declared_scopes'));
});

test('the modal says so when the app declares no scopes, and issues an API token without a scope', function (): void {
    config(['system.api.scopes' => []]);

    apiTokenModal($this->apiClient)
        ->call('show')
        ->assertSee(__('api/api-tokens.no_declared_scopes'))
        ->call('save')
        ->assertHasNoErrors();

    expect($this->apiClient->tokens()->sole()->scopes)->toBe([]);
});

test('a scope that the app does not declare is refused', function (): void {
    apiTokenModal($this->apiClient)
        ->call('show')
        ->set('form.scopes', ['orders.delete'])
        ->call('save')
        ->assertHasErrors(['form.scopes.0' => 'in']);

    expect($this->apiClient->tokens()->exists())->toBeFalse();
});

test('issuing at the limit of active API tokens shows the limit message', function (): void {
    ApiToken::factory()->for($this->apiClient)->count(2)->create();

    apiTokenModal($this->apiClient)
        ->call('show')
        ->call('save')
        ->assertHasErrors('tokens')
        ->assertSee(__('api/api-clients.token_limit', ['max' => 2]));

    expect($this->apiClient->tokens()->count())->toBe(2);
});

test('rotating needs update', function (): void {
    $apiToken = ApiToken::factory()->for($this->apiClient)->create();
    revokePermissions($this->actor, 'system.api-clients.update');

    apiTokenModal($this->apiClient)
        ->call('rotate', $apiToken->id)
        ->assertForbidden();

    expect($this->apiClient->tokens()->count())->toBe(1);
});

test('rotating shows the new plain API token one time and keeps the old one for the overlap days', function (): void {
    $this->freezeSecond();
    config(['system.api.rotation_overlap_days' => 3]);
    $old = ApiToken::factory()->for($this->apiClient)->create(['scopes' => ['orders.write']]);

    $modal = apiTokenModal($this->apiClient)
        ->call('rotate', $old->id)
        ->assertHasNoErrors()
        ->assertSet('showing', true)
        ->assertSee(__('api/api-tokens.issued_warning'))
        ->assertDispatched(ApiTokenModal::event());

    $tokens = plainTokensIn($modal->html());

    expect($tokens)->toHaveCount(1)
        ->and(json_encode($modal->snapshot))->not->toContain($tokens[0])
        ->and(ApiToken::findByToken($tokens[0])?->scopes)->toBe(['orders.write'])
        ->and($old->fresh()?->expires_at?->equalTo(now()->addDays(3)))->toBeTrue();
});

test('rotating at the limit of active API tokens shows the limit message and keeps the modal closed', function (): void {
    [$old] = ApiToken::factory()->for($this->apiClient)->count(2)->create();

    apiTokenModal($this->apiClient)
        ->call('rotate', $old->id)
        ->assertSet('showing', false)
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === __('api/api-clients.token_limit', ['max' => 2]));

    expect($this->apiClient->tokens()->count())->toBe(2);
});

test('a revoked or expired API token cannot be rotated', function (string $state): void {
    $apiToken = ApiToken::factory()->for($this->apiClient)->{$state}()->create();

    apiTokenModal($this->apiClient)
        ->call('rotate', $apiToken->id)
        ->assertForbidden();
})->with(['revoked', 'expired']);

test('an API token of another API client is not found', function (): void {
    $apiToken = ApiToken::factory()->create();

    apiTokenModal($this->apiClient)
        ->call('rotate', $apiToken->id)
        ->assertNotFound();
});

test('the plain API token shows in full in a read-only field that wraps, with a copy button', function (): void {
    $modal = apiTokenModal($this->apiClient)
        ->call('show')
        ->call('save');

    $document = HTMLDocument::createFromString($modal->html(), LIBXML_NOERROR);
    $field = $document->querySelector('textarea[readonly]');

    expect(plainTokensIn((string) $field?->textContent))->toHaveCount(1)
        ->and($document->querySelector('[data-copy-token]')?->getAttribute('x-on:click'))->toContain('$refs.token.value');
});

test('saving needs an open modal', function (): void {
    apiTokenModal($this->apiClient)
        ->call('save')
        ->assertForbidden();

    expect($this->apiClient->tokens()->exists())->toBeFalse();
});
