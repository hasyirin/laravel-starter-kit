<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\Fixtures\Livewire\LetterDataFieldsPage;

beforeEach(function (): void {
    Route::livewire('/browser/letter-data-fields', LetterDataFieldsPage::class)->middleware('web');

    $this->actingAs(User::factory()->create());
});

function dataFieldsScript(string $body): string
{
    $helpers = <<<'SCRIPT'
        const inputs = (selector) => [...document.querySelectorAll(selector)];
        const values = (selector) => inputs(selector).map((input) => input.value);
        const typeInto = (input, value) => {
            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
        };
        const root = () => Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
        SCRIPT;

    return browserScript("{$helpers}\n{$body}");
}

test('list rows can be added, typed into, and removed from the middle', function (): void {
    $page = visit('/browser/letter-data-fields');

    expect($page->script(dataFieldsScript(<<<'SCRIPT'
        const rows = 'input[wire\\:model^="data.items."]';

        document.querySelector('[data-list-add]').click();

        if (! await until(() => inputs(rows).length === 4)) {
            return 'add failed';
        }

        typeInto(inputs(rows)[3], 'd');
        inputs('[data-list-remove]')[1].click();

        if (! await until(() => inputs(rows).length === 3)) {
            return 'remove failed';
        }

        await tick();

        return [values(rows), root().$get('data.items')];
        SCRIPT)))->toBe([['a', 'c', 'd'], ['a', 'c', 'd']]);

    $page->assertNoJavaScriptErrors();
});

test('table rows can be added, typed into, and removed from the middle', function (): void {
    $page = visit('/browser/letter-data-fields');

    expect($page->script(dataFieldsScript(<<<'SCRIPT'
        const cells = 'input[wire\\:model$=".item"]';

        document.querySelector('[data-row-add]').click();

        if (! await until(() => inputs(cells).length === 2)) {
            return 'first add failed';
        }

        typeInto(inputs(cells)[1], 'Ink');
        document.querySelector('[data-row-add]').click();

        if (! await until(() => inputs(cells).length === 3)) {
            return 'second add failed';
        }

        typeInto(inputs(cells)[2], 'Cap');
        inputs('[data-row-remove]')[1].click();

        if (! await until(() => inputs(cells).length === 2)) {
            return 'remove failed';
        }

        await tick();

        return [values(cells), root().$get('data.lines').map((row) => row.item)];
        SCRIPT)))->toBe([['Pen', 'Cap'], ['Pen', 'Cap']]);

    $page->assertNoJavaScriptErrors();
});
