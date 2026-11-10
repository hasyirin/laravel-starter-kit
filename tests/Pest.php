<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

pest()->beforeEach(function (): void {
    app()->detectEnvironment(fn (): string => 'local');
})->in('Browser');

/**
 * @param  string|array<int, string>  ...$permissions
 */
function grantPermissions(User $user, string|array ...$permissions): void
{
    $profile = $user->profile;

    giveProfile(
        $user,
        roles: collect($profile?->getRoleNames()),
        permissions: collect($profile?->permissions)->pluck('name')->merge(collect($permissions)->flatten()),
    );
}

/**
 * @param  string|array<int, string>  ...$permissions
 */
function revokePermissions(User $user, string|array ...$permissions): void
{
    $profile = $user->profile;

    giveProfile(
        $user,
        roles: collect($profile?->getRoleNames()),
        permissions: collect($profile?->permissions)->pluck('name')->diff(collect($permissions)->flatten()),
    );
}

/**
 * @param  Role|string|array<int, Role|string>  ...$roles
 */
function grantRoles(User $user, Role|string|array ...$roles): void
{
    $profile = $user->profile;
    $names = collect($roles)->flatten()->map(fn (Role|string $role): string => $role instanceof Role ? $role->name : $role);

    giveProfile(
        $user,
        roles: collect($profile?->getRoleNames())->merge($names),
        permissions: collect($profile?->permissions)->pluck('name'),
    );
}

/**
 * @param  Collection<int, string>  $roles
 * @param  Collection<int, string>  $permissions
 */
function giveProfile(User $user, Collection $roles, Collection $permissions): void
{
    Profile::factory()
        ->for($user)
        ->state($user->profile?->only(['organization_id', 'branch_id', 'department_id', 'position_id']) ?? [])
        ->withRoles(...$roles->unique()->values()->all())
        ->withPermissions(...$permissions->unique()->values()->all())
        ->create();

    $user->unsetRelation('profile');
}

function callLivewireAction(TestResponse $page, string $component, string $method): TestResponse
{
    preg_match_all('/wire:snapshot="([^"]+)"/', (string) $page->getContent(), $matches);

    $snapshot = collect($matches[1])
        ->map(fn (string $snapshot): string => html_entity_decode($snapshot))
        ->first(fn (string $snapshot): bool => data_get(json_decode($snapshot, true), 'memo.name') === $component);

    $payload = json_encode(['components' => [['snapshot' => $snapshot, 'updates' => [], 'calls' => [['method' => $method, 'params' => [], 'metadata' => []]]]]]);

    return test()->call('POST', resolve('livewire')->getUpdateUri(), server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => '*/*', 'HTTP_X_LIVEWIRE' => '1'], content: $payload);
}

function browserScript(string $body): string
{
    $helpers = <<<'SCRIPT'
        const tick = () => new Promise((resolve) => setTimeout(resolve, 50));
        const until = async (check) => {
            for (let attempt = 0; attempt < 100; attempt++) {
                if (check()) {
                    return true;
                }

                await new Promise((resolve) => setTimeout(resolve, 100));
            }

            return false;
        };
        const fieldElement = (model) => document.querySelector(`[data-input-model="${model}"], [name="${model}"], [wire\\:model="${model}"]`);
        const control = (model) => fieldElement(model).closest('[data-flux-field]').querySelector('[data-input-control]');
        const wire = (element) => Livewire.find(element.closest('[wire\\:id]').getAttribute('wire:id'));
        const property = (model) => wire(fieldElement(model)).$get(model);
        const setProperty = async (model, value) => {
            await wire(fieldElement(model)).$set(model, value);
            await tick();
        };
        SCRIPT;

    return "async () => {\n{$helpers}\n{$body}\n}";
}
