<?php

declare(strict_types=1);

use App\Domain\Shared\Documentation\Sections\Transitions;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use Illuminate\Support\Arr;
use Tests\Fixtures\Models\Ticket;
use Tests\Fixtures\Policies\TicketPolicy;

test('the table shows the permission and the roles of each step', function (): void {
    config(['system.permissions.internal.tickets' => TicketPolicy::PERMISSIONS]);
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    Role::query()->create(['name' => 'reviewer'])->givePermissionTo(['internal.tickets.in-review', 'internal.tickets.returned']);
    Role::query()->create(['name' => 'approver'])->givePermissionTo(['internal.tickets.approve', 'internal.tickets.rejected']);

    expect(new Transitions()->render(['model' => Ticket::class, 'field' => 'state']))
        ->toMatch('/^\| From +\| To +\| Step +\| Permission +\| Roles +\|$/m')
        ->toMatch('/^\| Open +\| In Review +\| in-review +\| `internal\.tickets\.in-review` +\| reviewer +\|$/m')
        ->toMatch('/^\| Returned +\| In Review +\| in-review +\| `internal\.tickets\.in-review` +\| reviewer +\|$/m')
        ->toMatch('/^\| In Review +\| Approved +\| approve +\| `internal\.tickets\.approve` +\| approver +\|$/m')
        ->toMatch('/^\| In Review +\| Turned Down +\| rejected +\| `internal\.tickets\.rejected` +\| approver +\|$/m')
        ->toEndWith('A role with `bypass-authorization` passes every check.');
});

test('the table marks a step whose permission is not in the config', function (): void {
    config(['system.permissions.internal.tickets' => Arr::except(TicketPolicy::PERMISSIONS, 'rejected')]);
    $this->seed(PermissionSeeder::class);

    expect(new Transitions()->render(['model' => Ticket::class, 'field' => 'state']))
        ->toMatch('/^\| In Review +\| Turned Down +\| rejected +\| Not in config\/system\.php +\| +\|$/m')
        ->not->toContain('passes every check');
});
