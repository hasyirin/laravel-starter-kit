<?php

declare(strict_types=1);

use App\Domain\Shared\Documentation\Sections\Swimlanes;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use Tests\Fixtures\Models\Ticket;
use Tests\Fixtures\Policies\TicketPolicy;

test('each lane holds the steps that one group of roles can do', function (): void {
    config(['system.permissions.internal.tickets' => TicketPolicy::PERMISSIONS]);
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    Role::query()->create(['name' => 'reviewer'])->givePermissionTo(['internal.tickets.in-review', 'internal.tickets.returned']);
    Role::query()->create(['name' => 'approver'])->givePermissionTo(['internal.tickets.approve']);

    expect(new Swimlanes()->render(['model' => Ticket::class, 'field' => 'state']))->toBe(<<<'MD'
        ```mermaid
        flowchart LR
            subgraph lane_1["approver"]
                in_review__approved["Approve"]
            end
            subgraph lane_2["reviewer"]
                open__in_review["In Review"]
                returned__in_review["In Review"]
                in_review__returned["Returned"]
            end
            subgraph lane_3["No role"]
                in_review__rejected["Rejected"]
            end
            open__in_review --> in_review__returned
            open__in_review --> in_review__approved
            open__in_review --> in_review__rejected
            returned__in_review --> in_review__returned
            returned__in_review --> in_review__approved
            returned__in_review --> in_review__rejected
            in_review__returned --> returned__in_review
        ```

        A role with `bypass-authorization` passes every check.
        MD);
});
