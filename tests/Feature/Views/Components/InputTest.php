<?php

declare(strict_types=1);

use Dom\HTMLDocument;
use Illuminate\View\ViewException;

test('a required input shows an asterisk that screen readers skip, and marks the input required', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input label="Product code" wire:model="code" required />'), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-label] span[aria-hidden="true"]')?->textContent)->toBe('*')
        ->and($document->querySelector('input[data-input-control]')?->hasAttribute('required'))->toBeTrue();
});

test('other attributes reach the input', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input label="Email" type="email" placeholder="name@example.com" autocomplete="email" />'), LIBXML_NOERROR);

    $input = $document->querySelector('input[data-input-control]');

    expect([$input?->getAttribute('type'), $input?->getAttribute('placeholder'), $input?->getAttribute('autocomplete')])->toBe(['email', 'name@example.com', 'email']);
});

test('the description sits between the label and the input, and the trailing description below the input', function (): void {
    $view = $this->blade('<x-input label="Product code" description="Printed on the receipt." description:trailing="You can change it later." />');

    $view->assertSeeInOrder(['Product code', 'Printed on the receipt.', 'data-input-control', 'You can change it later.'], false);
});

test('the hint sits at the end of the label row, outside the label', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input label="Product code" hint="6 characters" />'), LIBXML_NOERROR);

    expect(trim($document->querySelector('[data-input-label-row] [data-input-hint]')?->textContent ?? ''))->toBe('6 characters')
        ->and($document->querySelector('[data-flux-label] [data-input-hint]'))->toBeNull();
});

test('actions sit at the end of the label row, outside the label', function (): void {
    $view = $this->blade('<x-input label="Reference" hint="Printed on the receipt"><x-slot:actions><button type="button" data-test="print">Print</button></x-slot:actions></x-input>');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    expect($document->querySelector('[data-input-label-row] [data-input-actions] [data-test="print"]'))->not->toBeNull()
        ->and($document->querySelector('[data-flux-label] [data-test="print"]'))->toBeNull();
    $view->assertSeeInOrder(['Reference', 'Printed on the receipt', 'data-test="print"'], false);
});

test('a text prefix and suffix join the input in one group', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input label="Website" prefix="https://" suffix=".com" />'), LIBXML_NOERROR);

    expect(trim($document->querySelector('[data-flux-input-group] > [data-flux-input-group-prefix]')?->textContent ?? ''))->toBe('https://')
        ->and($document->querySelector('[data-flux-input-group] input[data-input-control]'))->not->toBeNull()
        ->and(trim($document->querySelector('[data-flux-input-group] > [data-flux-input-group-suffix]')?->textContent ?? ''))->toBe('.com');
});

test('a prefix slot goes into the group as it is', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input label="Phone"><x-slot:prefix><span data-test="country">+60</span></x-slot:prefix></x-input>'), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-input-group] > [data-test="country"]'))->not->toBeNull()
        ->and($document->querySelector('[data-flux-input-group-prefix]'))->toBeNull();
});

test('the size reaches the input and its prefix', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input label="Amount" prefix="RM" size="sm" />'), LIBXML_NOERROR);

    expect($document->querySelector('input[data-input-control]')?->getAttribute('class'))->toContain('h-8')
        ->and($document->querySelector('[data-flux-input-group-prefix]')?->getAttribute('class'))->toContain('px-3!');
});

test('the input shows the error message of its property', function (string $template): void {
    $view = $this->withViewErrors(['email' => 'The email must be a valid address.'])->blade($template);

    $view->assertSee('The email must be a valid address.');
})->with([
    'bound with wire:model' => ['<x-input label="Email" wire:model="email" />'],
    'posted by name' => ['<x-input label="Email" name="email" />'],
]);

test('no-error-message hides the message but keeps the input marked invalid', function (): void {
    $view = $this->withViewErrors(['email' => 'The email must be a valid address.'])->blade('<x-input label="Email" wire:model="email" no-error-message />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    expect((string) $view)->not->toContain('The email must be a valid address.')
        ->and($document->querySelector('input[data-input-control]')?->getAttribute('aria-invalid'))->toBe('true');
});

test('only case control or a default turns on the input field behavior', function (string $template, ?string $behavior): void {
    $document = HTMLDocument::createFromString((string) $this->blade($template), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-field]')?->getAttribute('x-data'))->toBe($behavior);
})->with([
    'a plain input' => ['<x-input label="Code" />', null],
    'case control' => ['<x-input label="Code" case="upper" />', 'inputField'],
    'a default' => ['<x-input label="Code" default="MY" />', 'inputField'],
]);

test('case control tells the input field behavior which case to use', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input label="Code" case="upper" />'), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-field]')?->getAttribute('data-input-case'))->toBe('upper');
});

test('an unknown case is rejected', function (): void {
    expect(fn () => $this->blade('<x-input label="Code" case="shout" />'))->toThrow(ViewException::class, 'Invalid case [shout]');
});

test('a default adds a checkbox below the input and leaves wire:model on the input', function (): void {
    $view = $this->blade('<x-input label="State" wire:model.live.blur="state" default="Selangor" />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    $field = $document->querySelector('[data-flux-field]');

    $view->assertSeeInOrder(['data-input-control', '<ui-checkbox'], false);
    expect($field?->getAttribute('data-input-default'))->toBe('Selangor')
        ->and($field?->getAttribute('data-input-model'))->toBe('state')
        ->and($document->querySelector('input[data-input-control]')?->getAttribute('wire:model.live.blur'))->toBe('state')
        ->and(trim($document->querySelector('ui-checkbox + [data-flux-label]')?->textContent ?? ''))->toBe('Use the default: Selangor');
});

test('default-label replaces the label of the default checkbox', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input label="State" default="Selangor" default-label="Same as the office" />'), LIBXML_NOERROR);

    expect(trim($document->querySelector('ui-checkbox + [data-flux-label]')?->textContent ?? ''))->toBe('Same as the office');
});

test('the default checkbox is disabled with the input', function (string $attribute): void {
    $document = HTMLDocument::createFromString((string) $this->blade("<x-input label=\"Branch\" default=\"HQ\" {$attribute} />"), LIBXML_NOERROR);

    expect($document->querySelector('ui-checkbox')?->hasAttribute('disabled'))->toBeTrue();
})->with(['disabled', 'readonly']);
