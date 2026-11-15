<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $actor = User::factory()->create();
    grantPermissions($actor, 'system.access', 'system.api-clients.create');
    $this->actingAs($actor);
});

test('the allowed IP address rows line up when only some of them have an error', function (): void {
    $page = visit(route('system.api-clients.create', absolute: false));

    [$buttons, $inputs] = $page->script(browserScript(<<<'SCRIPT'
        const component = wire(document.querySelector('[data-row-add]'));
        await component.$set('form.name', 'Billing');
        await component.$set('form.allowed_ip_addresses', [
            { address: 'not-an-ip', description: 'HQ firewall' },
            { address: '192.0.2.10', description: 'Backup office' },
        ]);
        await component.$call('save');
        await until(() => document.querySelectorAll('[data-row-remove]').length === 2 && document.querySelector('[data-flux-error]:not(.hidden)'));

        const edges = (selector) => [...document.querySelectorAll(selector)].map((element) => {
            const box = element.getBoundingClientRect();

            return [Math.round(box.left), Math.round(box.right)];
        });

        return [
            edges('[data-row-remove]'),
            edges('input[wire\\:model$=".address"]'),
        ];
        SCRIPT));

    expect($buttons[0])->toBe($buttons[1])
        ->and($inputs[0])->toBe($inputs[1]);
});
