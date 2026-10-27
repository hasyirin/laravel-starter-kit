<?php

declare(strict_types=1);

use Dom\HTMLDocument;

test('a password input hides the password and has a button to show it', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-input.password label="Password" wire:model="password" autocomplete="current-password" />'), LIBXML_NOERROR);
    $input = $document->querySelector('input[data-input-control]');

    expect([$input?->getAttribute('type'), $input?->getAttribute('autocomplete'), $input?->getAttribute('wire:model')])->toBe(['password', 'current-password', 'password'])
        ->and($document->querySelector('[x-data="fluxInputViewable"]'))->not->toBeNull();
});

test('the hint and actions slots reach the label row', function (): void {
    $view = $this->blade(
        '<x-input.password label="Password"><x-slot:hint><a href="/forgot-password" data-test="forgot">Forgot?</a></x-slot:hint><x-slot:actions><button type="button" data-test="generate">Generate</button></x-slot:actions></x-input.password>',
    );

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    expect($document->querySelector('[data-input-label-row] [data-input-hint] [data-test="forgot"]'))->not->toBeNull()
        ->and($document->querySelector('[data-input-label-row] [data-input-actions] [data-test="generate"]'))->not->toBeNull();
});

test('the label, badge, and description slots reach the field', function (): void {
    $view = $this->blade(
        '<x-input.password wire:model="password"><x-slot:label>Slot label</x-slot:label><x-slot:badge>Slot badge</x-slot:badge><x-slot:description>Slot description</x-slot:description></x-input.password>',
    );

    $view->assertSee('Slot label')->assertSee('Slot badge')->assertSee('Slot description');
});
