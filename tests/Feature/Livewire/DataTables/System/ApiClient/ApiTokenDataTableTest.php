<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\System\ApiClient\ApiTokenDataTable;
use App\Livewire\Pages\System\ApiClient\ApiTokenModal;
use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Columns\TextColumn;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'system.access', 'system.api-clients.view', 'system.api-clients.update');
    $this->actingAs($this->actor);
    $this->apiClient = ApiClient::factory()->create();
});

test('the list shows the API tokens of the API client with their state', function (): void {
    $active = ApiToken::factory()->for($this->apiClient)->create(['expires_at' => now()->addDays(31), 'scopes' => ['orders.read']]);
    $expiresSoon = ApiToken::factory()->for($this->apiClient)->create(['expires_at' => now()->addDays(30)]);
    $expired = ApiToken::factory()->for($this->apiClient)->expired()->create();
    $revoked = ApiToken::factory()->for($this->apiClient)->revoked()->neverExpires()->create();
    $foreign = ApiToken::factory()->create();

    Livewire::test(ApiTokenDataTable::class, ['apiClient' => $this->apiClient])
        ->assertCanSeeTableRecords([$active, $expiresSoon, $expired, $revoked])
        ->assertCanNotSeeTableRecords([$foreign])
        ->assertTableColumnStateSet('state', __('api/api-tokens.states.active'), $active)
        ->assertTableColumnStateSet('state', __('api/api-tokens.states.expires_soon'), $expiresSoon)
        ->assertTableColumnStateSet('state', __('api/api-tokens.states.expired'), $expired)
        ->assertTableColumnStateSet('state', __('api/api-tokens.states.revoked'), $revoked)
        ->assertTableColumnStateSet('scopes', ['orders.read'], $active)
        ->assertSee($active->last_four)
        ->assertSee(__('api/api-tokens.never'));
});

test('an API token that was never used says so', function (): void {
    $apiToken = ApiToken::factory()->for($this->apiClient)->create(['last_used_at' => null]);

    Livewire::test(ApiTokenDataTable::class, ['apiClient' => $this->apiClient])
        ->assertTableColumnExists('last_used_at', fn (TextColumn $column): bool => $column->getState() === null && $column->getPlaceholder() === __('api/api-tokens.never'), $apiToken);
});

test('the list needs view', function (): void {
    revokePermissions($this->actor, 'system.api-clients.view');

    Livewire::test(ApiTokenDataTable::class, ['apiClient' => $this->apiClient])->assertForbidden();
});

test('the issue action opens the API token modal', function (): void {
    Livewire::test(ApiTokenDataTable::class, ['apiClient' => $this->apiClient])
        ->callAction(TestAction::make('create')->table())
        ->assertDispatchedTo(ApiTokenModal::class, 'show');
});

test('the actions are hidden without update', function (): void {
    revokePermissions($this->actor, 'system.api-clients.update');
    $apiToken = ApiToken::factory()->for($this->apiClient)->create();

    Livewire::test(ApiTokenDataTable::class, ['apiClient' => $this->apiClient])
        ->assertActionHidden(TestAction::make('create')->table())
        ->assertActionHidden(TestAction::make('rotate')->table($apiToken))
        ->assertActionHidden(TestAction::make('revoke')->table($apiToken));
});

test('the rotate action asks for confirmation like revoke, then has the API token modal rotate the API token', function (): void {
    config(['system.api.rotation_overlap_days' => 3]);
    $apiToken = ApiToken::factory()->for($this->apiClient)->create(['last_four' => '1234']);
    $description = __('api/api-tokens.rotate_description', ['ending' => __('api/api-tokens.ending', ['last_four' => '1234']), 'days' => 3]);

    Livewire::test(ApiTokenDataTable::class, ['apiClient' => $this->apiClient])
        ->assertActionExists(
            TestAction::make('rotate')->table($apiToken),
            fn (Action $action): bool => $action->isConfirmationRequired()
                && $action->getModalHeading() === __('api/api-tokens.rotate_heading')
                && $action->getModalDescription() === $description
                && $action->getModalSubmitActionLabel() === __('api/api-tokens.rotate'),
        )
        ->callAction(TestAction::make('rotate')->table($apiToken))
        ->assertDispatchedTo(ApiTokenModal::class, 'rotate', id: $apiToken->id);

    expect($this->apiClient->tokens()->count())->toBe(1);
});

test('rotate is offered only for an active API token', function (): void {
    $active = ApiToken::factory()->for($this->apiClient)->create();
    $expired = ApiToken::factory()->for($this->apiClient)->expired()->create();
    $revoked = ApiToken::factory()->for($this->apiClient)->revoked()->create();

    Livewire::test(ApiTokenDataTable::class, ['apiClient' => $this->apiClient])
        ->assertActionVisible(TestAction::make('rotate')->table($active))
        ->assertActionHidden(TestAction::make('rotate')->table($expired))
        ->assertActionHidden(TestAction::make('rotate')->table($revoked));
});

test('rotate is hidden while the API client has no free slot for an active API token', function (): void {
    [$active] = ApiToken::factory()->for($this->apiClient)->count(2)->create();

    Livewire::test(ApiTokenDataTable::class, ['apiClient' => $this->apiClient])
        ->assertActionHidden(TestAction::make('rotate')->table($active));
});

test('the revoke action revokes the API token', function (): void {
    $apiToken = ApiToken::factory()->for($this->apiClient)->create();

    Livewire::test(ApiTokenDataTable::class, ['apiClient' => $this->apiClient])
        ->callAction(TestAction::make('revoke')->table($apiToken))
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === __('api/api-tokens.revoked'));

    expect($apiToken->fresh()->revoked)->toBeTrue();
});

test('revoke is hidden for a revoked or expired API token', function (string $state): void {
    $apiToken = ApiToken::factory()->for($this->apiClient)->{$state}()->create();

    Livewire::test(ApiTokenDataTable::class, ['apiClient' => $this->apiClient])
        ->assertActionHidden(TestAction::make('revoke')->table($apiToken));
})->with(['revoked', 'expired']);

test('the list refreshes after the modal issues an API token', function (): void {
    $table = Livewire::test(ApiTokenDataTable::class, ['apiClient' => $this->apiClient])->assertCountTableRecords(0);
    $apiToken = ApiToken::factory()->for($this->apiClient)->create();

    $table->dispatch(ApiTokenModal::event())->assertCanSeeTableRecords([$apiToken]);
});
