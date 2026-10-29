<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

pest()->beforeEach(function (): void {
    app()->detectEnvironment(fn (): string => 'local');
})->in('Browser');

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
