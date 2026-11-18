<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions(
        $this->actor,
        'internal.access',
        'internal.controls.users.view',
        'internal.controls.roles.view',
        'internal.controls.organizations.view',
        'internal.controls.geography.view',
        'system.access',
        'system.api-clients.view',
    );
    $this->actingAs($this->actor);
});

test('a malformed record id gives 404 and is not sent to the database', function (Closure $url, string $id): void {
    $page = $url($id);
    $bindings = collect();
    DB::listen(fn (QueryExecuted $query) => $bindings->push(...$query->bindings));

    $response = $this->get($page);

    $response->assertNotFound();

    expect($bindings)->not->toContain($id);
})->with([
    'a user' => [fn (string $id): string => route('internal.controls.users.show', $id)],
    'a role' => [fn (string $id): string => route('internal.controls.roles.show', $id)],
    'an organization' => [fn (string $id): string => route('internal.controls.organizations.show', $id)],
    'a department of an organization' => [fn (string $id): string => route('internal.controls.organizations.departments.show', [Organization::factory()->create(), $id])],
    'a branch of an organization' => [fn (string $id): string => route('internal.controls.organizations.branches.show', [Organization::factory()->create(), $id])],
    'a country' => [fn (string $id): string => route('internal.controls.geography.countries.show', $id)],
    'a state of a country' => [fn (string $id): string => route('internal.controls.geography.countries.states.show', [Country::factory()->create(), $id])],
    'a district of a state' => [function (string $id): string {
        $state = State::factory()->create();

        return route('internal.controls.geography.countries.states.districts.show', [$state->country, $state, $id]);
    }],
    'a city of a state' => [function (string $id): string {
        $state = State::factory()->create();

        return route('internal.controls.geography.countries.states.cities.show', [$state->country, $state, $id]);
    }],
    'an API client' => [fn (string $id): string => route('system.api-clients.show', $id)],
    'a letter preview' => [fn (string $id): string => route('letters.preview', $id)],
])->with([
    'letters' => ['abc'],
    'more digits than a bigint holds' => ['99999999999999999999'],
]);
