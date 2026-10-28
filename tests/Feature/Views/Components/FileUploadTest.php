<?php

declare(strict_types=1);

use Dom\HTMLDocument;
use Illuminate\View\ViewException;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\DocumentsHost;

test('a file upload puts the upload component under a label row that shows the error of its property', function (): void {
    $view = $this->withViewErrors(['documents' => 'Add at least one document.'])->blade('<x-file-upload label="Documents" wire:model="documents" rules="mimes:pdf|max:1024" multiple required />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    $upload = $document->querySelector('[data-flux-field] > [wire\:id] ui-file-upload');

    expect($upload?->hasAttribute('multiple'))->toBeTrue()
        ->and($upload?->getAttribute('accept'))->toBe('.pdf')
        ->and($document->querySelector('[data-input-label] > span[aria-hidden="true"]')?->textContent)->toBe('*');
    $view->assertSeeInOrder(['Documents', 'Drop files here or click to browse', 'PDF up to 1 MB', 'Add at least one document.']);
});

test('the upload component starts with the items of the property it is bound to', function (): void {
    $component = Livewire::test(DocumentsHost::class);

    $component->assertSeeHtml('contract.pdf')
        ->assertSeeHtml('aria-label="Download contract.pdf"');
});

test('a file upload needs wire:model', function (): void {
    expect(fn () => $this->blade('<x-file-upload label="Documents" :rules="[\'mimes:pdf\']" />'))->toThrow(ViewException::class, 'x-file-upload needs wire:model.');
});
