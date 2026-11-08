<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Pages\Internal\Controls\User\Show;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.users.view', 'internal.controls.organizations.view');
    $this->actingAs($this->actor);
    $this->subject = User::factory()->create();
    $this->organization = Organization::factory()->create();
    $this->department = Department::factory()->for($this->organization)->create();
    $this->branch = Branch::factory()->for($this->organization)->create();
});

dataset('user pages', [
    'organization' => fn (): array => [
        route('internal.controls.organizations.users.show', [test()->organization, test()->subject]),
        [route('internal.controls.organizations.index'), route('internal.controls.organizations.show', test()->organization)],
        route('internal.controls.organizations.show', ['organization' => test()->organization, 'tab' => 'users']),
    ],
    'department' => fn (): array => [
        route('internal.controls.organizations.departments.users.show', [test()->organization, test()->department, test()->subject]),
        [
            route('internal.controls.organizations.index'),
            route('internal.controls.organizations.show', test()->organization),
            route('internal.controls.organizations.show', ['organization' => test()->organization, 'tab' => 'departments']),
            route('internal.controls.organizations.departments.show', [test()->organization, test()->department]),
        ],
        route('internal.controls.organizations.departments.show', ['organization' => test()->organization, 'department' => test()->department, 'tab' => 'users']),
    ],
    'branch' => fn (): array => [
        route('internal.controls.organizations.branches.users.show', [test()->organization, test()->branch, test()->subject]),
        [
            route('internal.controls.organizations.index'),
            route('internal.controls.organizations.show', test()->organization),
            route('internal.controls.organizations.show', ['organization' => test()->organization, 'tab' => 'branches']),
            route('internal.controls.organizations.branches.show', [test()->organization, test()->branch]),
        ],
        route('internal.controls.organizations.branches.show', ['organization' => test()->organization, 'branch' => test()->branch, 'tab' => 'users']),
    ],
]);

test('a user opened from an organization, department, or branch leads back to its users tab', function (array $page): void {
    [$url, $crumbs, $tab] = $page;

    $html = $this->get($url)
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSeeInOrder([...$crumbs, $tab, $this->subject->email], false)
        ->assertDontSee(route('internal.controls.users.index'))
        ->getContent();

    expect(substr_count($html, 'href="'.$tab.'"'))->toBe(2);
})->with('user pages');

test('a user opened from an organization, department, or branch needs organization view', function (array $page): void {
    revokePermissions($this->actor, 'internal.controls.organizations.view');

    $this->get($page[0])->assertForbidden();
})->with('user pages');

test('a department or branch of another organization is not found', function (string $route, string $record): void {
    $other = Organization::factory()->create();

    $this->get(route($route, [$other, $this->{$record}, $this->subject]))->assertNotFound();
})->with([
    ['internal.controls.organizations.departments.users.show', 'department'],
    ['internal.controls.organizations.branches.users.show', 'branch'],
]);
