<?php

declare(strict_types=1);

use Dom\HTMLDocument;

test('a switch comes before its label, and align right puts the label first', function (string $template, string $order): void {
    $document = HTMLDocument::createFromString((string) $this->blade($template), LIBXML_NOERROR);

    expect($document->querySelector($order))->not->toBeNull()
        ->and($document->querySelector('ui-switch')?->getAttribute('wire:model'))->toBe('active');
})->with([
    'the default' => ['<x-switch label="Active" wire:model="active" />', 'ui-switch + [data-flux-label]'],
    'align right' => ['<x-switch label="Active" wire:model="active" align="right" />', '[data-flux-label] + ui-switch'],
]);
