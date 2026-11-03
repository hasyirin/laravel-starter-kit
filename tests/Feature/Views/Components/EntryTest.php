<?php

declare(strict_types=1);

use Dom\HTMLDocument;

function renderEntry(string $template): HTMLDocument
{
    return HTMLDocument::createFromString((string) test()->blade($template), LIBXML_NOERROR);
}

test('an entry shows its label in the term and its value in the description', function (): void {
    $document = renderEntry('<x-entry label="Created">3 Nov 2026</x-entry>');

    expect(trim($document->querySelector('dl > dt')?->textContent ?? ''))->toBe('Created')
        ->and(trim($document->querySelector('dl > dd')?->textContent ?? ''))->toBe('3 Nov 2026');
});

test('an entry puts the attributes of the caller on the list', function (): void {
    $list = renderEntry('<x-entry label="Created" class="entry-test" id="entry-id">3 Nov 2026</x-entry>')->querySelector('dl');

    expect($list?->getAttribute('id'))->toBe('entry-id')
        ->and($list?->classList->contains('entry-test'))->toBeTrue()
        ->and($list?->classList->contains('grid'))->toBeTrue();
});
