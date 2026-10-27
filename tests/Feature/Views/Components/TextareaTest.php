<?php

declare(strict_types=1);

use Dom\HTMLDocument;
use Illuminate\View\ViewException;

test('a textarea binds to its property, is required, and shows the error of its property', function (): void {
    $view = $this->withViewErrors(['notes' => 'The notes may not be longer than 500 characters.'])->blade('<x-textarea label="Notes" wire:model="notes" rows="3" required />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    $textarea = $document->querySelector('textarea[data-input-control]');

    expect($textarea?->getAttribute('wire:model'))->toBe('notes')
        ->and($textarea?->getAttribute('rows'))->toBe('3')
        ->and($textarea?->hasAttribute('required'))->toBeTrue()
        ->and($textarea?->getAttribute('aria-invalid'))->toBe('true')
        ->and($document->querySelector('[data-input-label] > span[aria-hidden="true"]')?->textContent)->toBe('*');
    $view->assertSee('The notes may not be longer than 500 characters.');
});

test('the slot of a textarea is its text', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-textarea label="Notes" name="notes">Deliver after 5 pm.</x-textarea>'), LIBXML_NOERROR);

    expect($document->querySelector('textarea[data-input-control]')?->textContent)->toBe('Deliver after 5 pm.');
});

test('only case control turns on the input field behavior', function (string $template, ?string $behavior, ?string $case): void {
    $field = HTMLDocument::createFromString((string) $this->blade($template), LIBXML_NOERROR)->querySelector('[data-flux-field]');

    expect([$field?->getAttribute('x-data'), $field?->getAttribute('data-input-case')])->toBe([$behavior, $case]);
})->with([
    'a plain textarea' => ['<x-textarea label="Notes" />', null, null],
    'case control' => ['<x-textarea label="Notes" case="sentence" />', 'inputField', 'sentence'],
]);

test('an unknown case is rejected', function (): void {
    expect(fn () => $this->blade('<x-textarea label="Notes" case="shout" />'))->toThrow(ViewException::class, 'Invalid case [shout]');
});
