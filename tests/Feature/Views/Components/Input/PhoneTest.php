<?php

declare(strict_types=1);

use Dom\HTMLDocument;

test('a phone starts at the app country, and country replaces it', function (string $template, string $country): void {
    config(['system.country' => 'SG']);

    $document = HTMLDocument::createFromString((string) $this->blade($template), LIBXML_NOERROR);

    expect($document->querySelector('ui-phone[data-input-control]')?->getAttribute('country'))->toBe($country);
})->with([
    'the app country' => ['<x-input.phone label="Phone" />', 'SG'],
    'its own country' => ['<x-input.phone label="Phone" country="ID" />', 'ID'],
]);

test('a phone binds to its property, makes the number required, and shows the error of its property', function (): void {
    $view = $this->withViewErrors(['phone' => 'The phone number is not valid.'])->blade('<x-input.phone label="Phone" wire:model="phone" required />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    expect($document->querySelector('ui-phone')?->getAttribute('wire:model.self'))->toBe('phone')
        ->and($document->querySelector('ui-phone input[type="tel"]')?->hasAttribute('required'))->toBeTrue();
    $view->assertSee('The phone number is not valid.');
});
