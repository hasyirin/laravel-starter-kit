<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\System\ApiClient\AuditLogDataTable;
use App\Livewire\Pages\System\ApiClient\AuditEntryModal;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create(['name' => 'Ada Lovelace']);
    grantPermissions($this->actor, 'system.access', 'system.api-clients.view', 'system.api-clients.view-audit-log');
    $this->actingAs($this->actor);
    $this->apiClient = ApiClient::factory()->create();
    $this->apiToken = ApiToken::factory()->for($this->apiClient)->create(['last_four' => 'Wx9z']);
    $this->other = ApiClient::factory()->create();
});

test('the list shows the API client log with the changes of its API tokens and who made them', function (): void {
    $onToken = Activity::query()->whereMorphedTo('subject', $this->apiToken)->firstOrFail();
    $onOther = Activity::query()->whereMorphedTo('subject', $this->other)->firstOrFail();

    Livewire::test(AuditLogDataTable::class, ['apiClient' => $this->apiClient])
        ->assertCanSeeTableRecords([$onToken])
        ->assertCanNotSeeTableRecords([$onOther])
        ->assertSee('Ada Lovelace')
        ->assertSee('API token ending in Wx9z');
});

test('the audit needs view-audit-log', function (string $component): void {
    revokePermissions($this->actor, 'system.api-clients.view-audit-log');

    Livewire::test($component, ['apiClient' => $this->apiClient])->assertForbidden();
})->with([AuditLogDataTable::class, AuditEntryModal::class]);

test('a row click opens the modal with the entry', function (): void {
    $entry = Activity::query()->whereMorphedTo('subject', $this->apiToken)->firstOrFail();

    Livewire::test(AuditLogDataTable::class, ['apiClient' => $this->apiClient])
        ->callAction(TestAction::make('view')->table($entry))
        ->assertDispatchedTo(AuditEntryModal::class, 'show', id: $entry->id);
});

test('the modal opens an entry of an API token of the API client', function (): void {
    $entry = Activity::query()->whereMorphedTo('subject', $this->apiToken)->firstOrFail();

    Livewire::test(AuditEntryModal::class, ['apiClient' => $this->apiClient])
        ->call('show', $entry->id)
        ->assertSet('showing', true);
});

test('the modal refuses an entry of another API client', function (): void {
    $entry = Activity::query()->whereMorphedTo('subject', $this->other)->firstOrFail();

    Livewire::test(AuditEntryModal::class, ['apiClient' => $this->apiClient])
        ->call('show', $entry->id)
        ->assertNotFound();
});
