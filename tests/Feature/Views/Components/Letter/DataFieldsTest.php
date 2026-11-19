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

test('a required field inside a group or a table row is marked required', function (): void {
    $document = renderDataFields(
        [
            Field::group('extra', 'Extra', [Field::text('reason'), Field::text('note')->optional()]),
            Field::table('lines', [Field::text('item'), Field::text('remark')->optional()])->optional(),
        ],
        ['_groups' => ['extra' => true], 'lines' => [['item' => 'Pen', 'remark' => null]]],
    );

    expect($document->querySelectorAll('[data-input-label] > span[aria-hidden="true"]'))->toHaveCount(2);
});

test('a locked field is disabled and the others are not', function (): void {
    $document = renderDataFields([Field::text('title'), Field::text('note')], locked: ['title']);

    expect($document->querySelector('input[wire\:model="form.data.title"]')?->hasAttribute('disabled'))->toBeTrue()
        ->and($document->querySelector('input[wire\:model="form.data.note"]')?->hasAttribute('disabled'))->toBeFalse();
});

test('a locked field of every type is disabled', function (Field $field, array $values, string $selector): void {
    $document = renderDataFields([$field], $values, [$field->name]);
    $controls = $document->querySelectorAll($selector);

    expect($controls)->not->toBeEmpty();

    foreach ($controls as $control) {
        expect($control->hasAttribute('disabled'))->toBeTrue();
    }
})->with([
    'text' => [Field::text('f'), [], 'input[wire\:model="form.data.f"]'],
    'lines' => [Field::lines('f'), [], 'textarea[wire\:model="form.data.f"]'],
    'date' => [Field::date('f'), [], 'ui-date-picker[wire\:model\.self="form.data.f"]'],
    'number' => [Field::number('f'), [], 'input[data-input-control]'],
    'yes no' => [Field::yesNo('f'), [], 'ui-switch[wire\:model="form.data.f"]'],
    'choice' => [Field::choice('f', ['a' => 'A']), [], '[wire\:model="form.data.f"]'],
    'list' => [Field::list('f'), ['f' => ['one', 'two']], 'input[wire\:model^="form.data.f."]'],
    'table' => [Field::table('f', [Field::text('c'), Field::number('n')]), ['f' => [['c' => 'x', 'n' => 1]]], 'input[wire\:model="form.data.f.0.c"], input[data-input-control]'],
    'group switch' => [Field::group('f', 'Group', [Field::text('c')]), [], 'ui-switch[wire\:model\.live="form.data._groups.f"]'],
]);

test('a locked image upload is disabled', function (): void {
    $html = (string) test()->blade('<x-letter.data-fields :$fields prefix="form.data" :locked="[\'logo\']" />', ['fields' => [Field::image('logo')]]);

    expect($html)->toContain('&quot;disabled&quot;:true');
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

    $rows = $document->querySelector('[data-row-add]')?->closest('[x-data]')?->getAttribute('x-data');
    $add = $document->querySelector('[data-row-add]')?->getAttribute('x-on:click');
    $remove = $document->querySelector('[data-row-remove]')?->getAttribute('x-on:click');

    expect($rows)->toContain('repeatableRows')->toContain('form.data.lines')
        ->and($add)->toContain('addRow')->toContain('item')
        ->and($remove)->toBe('removeRow(0)');
});

test('a locked list or table has no add or remove buttons', function (): void {
    $document = renderDataFields(
        [Field::list('items'), Field::table('lines', [Field::text('item')])],
        ['items' => ['one'], 'lines' => [['item' => 'Pen']]],
        ['items', 'lines'],
    );

    expect($document->querySelector('[data-list-add], [data-list-remove], [data-row-add], [data-row-remove]'))->toBeNull();
});

test('the error of a list row or a table cell shows one time, on its row', function (Field $field, array $values, string $errorKey, string $selector): void {
    $message = 'The row is invalid.';
    $view = test()->withViewErrors([$errorKey => $message])->blade('<x-letter.data-fields :$fields prefix="form.data" :$values />', ['fields' => [$field], 'values' => $values]);
    $rowField = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR)->querySelector($selector)?->closest('[data-flux-field]');

    expect(substr_count((string) $view, $message))->toBe(1)
        ->and($rowField?->textContent)->toContain($message);
})->with([
    'list' => [Field::list('items'), ['items' => ['', 'Pen']], 'form.data.items.0', 'input[wire\:model="form.data.items.0"]'],
    'table' => [Field::table('lines', [Field::text('item'), Field::text('note')]), ['lines' => [['item' => 'Pen', 'note' => ''], ['item' => 'Ink', 'note' => 'Blue']]], 'form.data.lines.0.note', 'input[wire\:model="form.data.lines.0.note"]'],
]);

test('the error of a list or a table itself shows one time, under the rows', function (Field $field, array $values, string $errorKey, string $addButton): void {
    $message = 'The field must be an array.';
    $view = test()->withViewErrors([$errorKey => $message])->blade('<x-letter.data-fields :$fields prefix="form.data" :$values />', ['fields' => [$field], 'values' => $values]);

    expect(substr_count((string) $view, $message))->toBe(1);
    $view->assertSeeInOrder([$addButton, $message], false);
})->with([
    'list' => [Field::list('items'), ['items' => ['Pen']], 'form.data.items', 'data-list-add'],
    'table' => [Field::table('lines', [Field::text('item')]), ['lines' => [['item' => 'Pen']]], 'form.data.lines', 'data-row-add'],
]);

test('the delete button of a list row or a table row does not shrink', function (Field $field, array $values, string $selector): void {
    $document = renderDataFields([$field], $values);

    expect($document->querySelector($selector)?->classList->contains('shrink-0'))->toBeTrue();
})->with([
    'list' => [Field::list('items'), ['items' => ['Pen']], '[data-list-remove]'],
    'table' => [Field::table('lines', [Field::text('item')]), ['lines' => [['item' => 'Pen']]], '[data-row-remove]'],
]);

test('the error of a group shows one time, at its group', function (Field $field, array $values, string $errorKey, string $switchKey): void {
    $message = 'The group is empty.';
    $view = test()->withViewErrors([$errorKey => $message])->blade('<x-letter.data-fields :$fields prefix="form.data" :$values />', ['fields' => [$field], 'values' => $values]);

    expect(substr_count((string) $view, $message))->toBe(1);
    $view->assertSeeInOrder([$switchKey, $message], false);
})->with([
    'top level' => [Field::group('extra', 'Extra', [Field::text('reason')]), ['_groups' => ['extra' => true]], 'form.data.extra', 'form.data._groups.extra'],
    'table row' => [Field::table('lines', [Field::text('item'), Field::group('extra', 'Extra', [Field::text('reason')])]), ['lines' => [['item' => 'Pen']]], 'form.data.lines.0.extra', 'form.data.lines.0._groups.extra'],
]);
