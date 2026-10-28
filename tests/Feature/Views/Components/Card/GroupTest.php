<?php

declare(strict_types=1);

use Dom\HTMLDocument;

test('a group holds its cards directly, in a form only when form is set', function (string $template, string $element): void {
    $document = HTMLDocument::createFromString((string) $this->blade($template), LIBXML_NOERROR);

    expect($document->querySelector('[data-card-group]')?->localName)->toBe($element)
        ->and($document->querySelectorAll('[data-card-group] > [data-flux-card]')->length)->toBe(2);
})->with([
    'a plain group' => ['<x-card.group><x-card>One</x-card><x-card>Two</x-card></x-card.group>', 'div'],
    'a form group' => ['<x-card.group form><x-card>One</x-card><x-card>Two</x-card></x-card.group>', 'form'],
]);

test('the attributes of a group reach its element', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-card.group form wire:submit="save" class="mt-6"><x-card>One</x-card></x-card.group>'), LIBXML_NOERROR);

    $group = $document->querySelector('form[data-card-group]');

    expect([$group?->getAttribute('wire:submit'), $group?->classList->contains('mt-6')])->toBe(['save', true]);
});
