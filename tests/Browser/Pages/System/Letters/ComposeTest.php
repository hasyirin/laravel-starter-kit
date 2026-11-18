<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Tests\Fixtures\Support\PermitLetter;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    config(['system.letters.types' => ['plain' => PermitLetter::class]]);
    $actor = User::factory()->create();
    grantPermissions($actor, 'system.access', 'system.letters.compose');
    $this->actingAs($actor);
});

test('the carbon copy rows line up when the error of one row is wider than its input', function (): void {
    $page = visit(route('system.letters.compose', absolute: false))->resize(560, 720);

    $rows = $page->script(browserScript(<<<'SCRIPT'
        const textInputs = 'input[wire\\:model$=".text"]';
        const component = wire(document.querySelector('form[wire\\:submit="print"]'));
        await component.$set('form.type', 'plain');
        await component.$set('form.carbon_copies', [
            { kind: 'text', target: null, text: 'x'.repeat(256) },
            { kind: 'text', target: null, text: 'Registry' },
        ]);
        await component.$call('print');

        if (! await until(() => document.querySelectorAll('[data-row-remove]').length === 2 && document.querySelector('[data-flux-error]:not(.hidden)'))) {
            return 'print showed no error';
        }

        const edges = (selector) => [...document.querySelectorAll(selector)].map((element) => {
            const box = element.getBoundingClientRect();

            return [Math.round(box.left), Math.round(box.right)];
        });
        const hasOwnError = (input) => input.closest('[data-flux-field]').querySelector('[data-flux-error]:not(.hidden)') !== null;

        return {
            errors: [...document.querySelectorAll(textInputs)].map(hasOwnError),
            buttons: edges('[data-row-remove]'),
            inputs: edges(textInputs),
        };
        SCRIPT));

    expect($rows)->toBeArray()
        ->and($rows['errors'])->toBe([true, false])
        ->and($rows['buttons'][0])->toBe($rows['buttons'][1])
        ->and($rows['inputs'][0])->toBe($rows['inputs'][1]);
    $page->assertNoJavaScriptErrors();
});
