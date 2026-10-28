<?php

declare(strict_types=1);

use Dom\HTMLDocument;

test('an editor binds to its property, takes its toolbar, and shows the error of its property', function (): void {
    $view = $this->withViewErrors(['notes' => 'The notes field is required.'])->blade('<x-editor label="Notes" wire:model="notes" toolbar="bold italic" required />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    $editor = $document->querySelector('ui-editor[data-input-control]');

    expect($editor?->getAttribute('wire:model'))->toBe('notes')
        ->and($editor?->getAttribute('class'))->toContain('border-red-500')
        ->and($editor?->querySelectorAll('ui-toolbar button'))->toHaveCount(2)
        ->and($document->querySelector('[data-input-label] > span[aria-hidden="true"]')?->textContent)->toBe('*');
    $view->assertSee('The notes field is required.');
});

test('the slot of an editor replaces its toolbar and content', function (): void {
    $editor = HTMLDocument::createFromString((string) $this->blade('<x-editor label="Notes" wire:model="notes"><flux:editor.content><p>Dear customer,</p></flux:editor.content></x-editor>'), LIBXML_NOERROR)->querySelector('ui-editor');

    expect($editor?->querySelector('ui-toolbar'))->toBeNull()
        ->and($editor?->querySelector('ui-editor-content')?->textContent)->toBe('Dear customer,');
});
