<?php

declare(strict_types=1);

use Dom\HTMLDocument;

test('a field wraps any control with the label row, the descriptions, and the error of its name', function (): void {
    $view = $this->withViewErrors(['country' => 'Choose a country.'])->blade(
        '<x-field label="Country" description="Where you live." description:trailing="It sets the tax rate." name="country" required><select data-test="control"></select></x-field>',
    );

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);

    expect($document->querySelector('[data-input-label-row] [data-input-label] > span[aria-hidden="true"]')?->textContent)->toBe('*');
    $view->assertSeeInOrder(['Country', 'Where you live.', 'data-test="control"', 'Choose a country.', 'It sets the tax rate.'], false);
});
