<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Support\Field;
use Dom\Element;
use Dom\HTMLDocument;

function renderDataFields(array $fields, array $values = [], array $locked = []): HTMLDocument
{
    $view = test()->blade('<x-letter.data-fields :$fields prefix="form.data" :$values :$locked />', ['fields' => $fields, 'values' => $values, 'locked' => $locked]);

    return HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
}

test('each field type renders its input bound to the prefix and the name', function (Field $field, string $selector): void {
    $document = renderDataFields([$field]);

    expect($document->querySelector($selector))->not->toBeNull();
})->with([
    'text' => [Field::text('title'), 'input[wire\:model="form.data.title"]'],
    'lines' => [Field::lines('body'), 'textarea[wire\:model="form.data.body"]'],
    'date' => [Field::date('issued_on'), 'ui-date-picker[wire\:model\.self="form.data.issued_on"]'],
    'number' => [Field::number('amount'), '[wire\:model="form.data.amount"]'],
    'yes no' => [Field::yesNo('urgent'), 'ui-switch[wire\:model="form.data.urgent"]'],
    'choice' => [Field::choice('colour', ['red' => 'Red', 'blue' => 'Blue']), '[wire\:model="form.data.colour"]'],
]);

test('a choice lists its options', function (): void {
    $document = renderDataFields([Field::choice('colour', ['red' => 'Red', 'blue' => 'Blue'])]);

    expect($document->body->textContent)->toContain('Red')->toContain('Blue');
});

test('an image renders the file upload bound to the field', function (): void {
    $html = (string) test()->blade('<x-letter.data-fields :$fields prefix="form.data" />', ['fields' => [Field::image('logo')]]);
    $upload = HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelector('ui-file-upload');

    expect($html)->toContain('form.data.logo')
        ->and($upload)->not->toBeNull()
        ->and($upload?->hasAttribute('multiple'))->toBeFalse();
});

test('a label comes from the field, or from its name', function (): void {
    $document = renderDataFields([Field::text('applicant_name'), Field::text('title')->label('Subject line')]);

    expect($document->body->textContent)->toContain('Applicant Name')->toContain('Subject line');
});

test('an optional field is not marked required', function (): void {
    $document = renderDataFields([Field::text('title'), Field::text('note')->optional()]);

    expect($document->querySelectorAll('[data-input-label] > span[aria-hidden="true"]'))->toHaveCount(1);
});

test('a locked field is disabled and the others are not', function (): void {
    $document = renderDataFields([Field::text('title'), Field::text('note')], locked: ['title']);

    expect($document->querySelector('input[wire\:model="form.data.title"]')?->hasAttribute('disabled'))->toBeTrue()
        ->and($document->querySelector('input[wire\:model="form.data.note"]')?->hasAttribute('disabled'))->toBeFalse();
});

test('a locked group disables the fields inside it', function (): void {
    $document = renderDataFields(
        [Field::group('extra', 'Extra', [Field::text('reason')])],
        ['_groups' => ['extra' => true]],
        ['extra'],
    );

    expect($document->querySelector('input[wire\:model="form.data.extra.reason"]')?->hasAttribute('disabled'))->toBeTrue();
});

test('a group switch is bound to the groups key and shows the fields only when on', function (bool $on): void {
    $document = renderDataFields(
        [Field::group('extra', 'Extra details', [Field::text('reason')])],
        ['_groups' => ['extra' => $on]],
    );

    expect($document->querySelector('ui-switch[wire\:model\.live="form.data._groups.extra"]'))->not->toBeNull()
        ->and($document->body->textContent)->toContain('Extra details')
        ->and($document->querySelector('input[wire\:model="form.data.extra.reason"]') instanceof Element)->toBe($on);
})->with([true, false]);

test('a list renders one input and one remove button for each row, and an add button', function (): void {
    $document = renderDataFields([Field::list('items')], ['items' => ['one', 'two']]);

    expect($document->querySelector('input[wire\:model="form.data.items.0"]'))->not->toBeNull()
        ->and($document->querySelector('input[wire\:model="form.data.items.1"]'))->not->toBeNull()
        ->and($document->querySelector('input[wire\:model="form.data.items.2"]'))->toBeNull()
        ->and($document->querySelectorAll('[data-list-remove]'))->toHaveCount(2)
        ->and($document->querySelector('[data-list-add]'))->not->toBeNull();
});

test('a table renders the columns of each row bound to the row index', function (): void {
    $document = renderDataFields(
        [Field::table('lines', [Field::text('item'), Field::number('quantity')])],
        ['lines' => [['item' => 'Pen', 'quantity' => 2], ['item' => 'Ink', 'quantity' => 1]]],
    );

    expect($document->querySelector('input[wire\:model="form.data.lines.0.item"]'))->not->toBeNull()
        ->and($document->querySelector('[wire\:model="form.data.lines.0.quantity"]'))->not->toBeNull()
        ->and($document->querySelector('input[wire\:model="form.data.lines.1.item"]'))->not->toBeNull()
        ->and($document->querySelectorAll('[data-row-remove]'))->toHaveCount(2)
        ->and($document->querySelector('[data-row-add]'))->not->toBeNull();
});

test('the add and remove buttons change the property in the browser', function (): void {
    $document = renderDataFields([Field::table('lines', [Field::text('item')])], ['lines' => [['item' => 'Pen']]]);

    $add = $document->querySelector('[data-row-add]')?->getAttribute('x-on:click');
    $remove = $document->querySelector('[data-row-remove]')?->getAttribute('x-on:click');

    expect($add)->toContain('$wire.$set')->toContain('form.data.lines')->toContain('item')
        ->and($remove)->toContain('$wire.$set')->toContain('form.data.lines')->toContain('filter');
});

test('a locked list or table has no add or remove buttons', function (): void {
    $document = renderDataFields(
        [Field::list('items'), Field::table('lines', [Field::text('item')])],
        ['items' => ['one'], 'lines' => [['item' => 'Pen']]],
        ['items', 'lines'],
    );

    expect($document->querySelector('[data-list-add], [data-list-remove], [data-row-add], [data-row-remove]'))->toBeNull();
});
