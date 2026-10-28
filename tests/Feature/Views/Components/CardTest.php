<?php

declare(strict_types=1);

use Dom\HTMLDocument;
use Illuminate\View\ViewException;

test('a card puts the heading, the subheading, and the actions in the header, the slot in the body, and the footer last', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade(
        '<x-card heading="Profile" subheading="Other users see this."><x-slot:actions><button type="button" data-test="edit">Edit</button></x-slot:actions><p data-test="content">Content</p><x-slot:footer>Saved.</x-slot:footer></x-card>',
    ), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-card] > [data-flux-card-header] + [data-flux-card-body] + [data-flux-card-footer]'))->not->toBeNull()
        ->and(trim($document->querySelector('[data-flux-card-header] > [data-flux-card-heading]')?->textContent ?? ''))->toBe('Profile')
        ->and(trim($document->querySelector('[data-flux-card-header] > [data-flux-card-subheading]')?->textContent ?? ''))->toBe('Other users see this.')
        ->and($document->querySelector('[data-flux-card-header] > [data-flux-card-actions] > [data-test="edit"]'))->not->toBeNull()
        ->and($document->querySelector('[data-flux-card-body] > [data-test="content"]'))->not->toBeNull()
        ->and(trim($document->querySelector('[data-flux-card-footer]')?->textContent ?? ''))->toBe('Saved.');
});

test('a card without a heading, a subheading, actions, or a footer renders only the body', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-card><p data-test="content">Content</p></x-card>'), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-card] > [data-flux-card-body]:only-child > [data-test="content"]'))->not->toBeNull();
});

test('a slot that holds only the markers of a false Livewire condition renders no part', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade(
        '<x-card heading="Profile"><!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]--><x-slot:footer><!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]--></x-slot:footer></x-card>',
    ), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-card] > [data-flux-card-header]:only-child'))->not->toBeNull();
});

test('a part bound to false renders nothing', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-card heading="Profile" :footer="false">Content</x-card>'), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-card-footer]'))->toBeNull();
});

test('a card without a heading still renders a header for its subheading and actions', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade(
        '<x-card subheading="Other users see this."><x-slot:actions><button type="button" data-test="edit">Edit</button></x-slot:actions>Content</x-card>',
    ), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-card-header] > [data-flux-card-heading]'))->toBeNull()
        ->and($document->querySelector('[data-flux-card-header] > [data-flux-card-subheading] + [data-flux-card-actions] > [data-test="edit"]'))->not->toBeNull();
});

test('the attributes of a slot reach its Flux part', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade(
        '<x-card><x-slot:heading level="2">Profile</x-slot:heading><x-slot:subheading class="max-w-prose">Other users see this.</x-slot:subheading><x-slot:actions class="gap-1">Edit</x-slot:actions>Content<x-slot:footer class="justify-end">Save</x-slot:footer></x-card>',
    ), LIBXML_NOERROR);

    expect($document->querySelector('h2[data-flux-card-heading]'))->not->toBeNull()
        ->and($document->querySelector('[data-flux-card-subheading]')?->classList->contains('max-w-prose'))->toBeTrue()
        ->and($document->querySelector('[data-flux-card-actions]')?->classList->contains('gap-1'))->toBeTrue()
        ->and($document->querySelector('[data-flux-card-footer]')?->classList->contains('justify-end'))->toBeTrue();
});

test('body-class reaches the body, and class stays on the card', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-card class="mt-6" body-class="grid gap-6">Content</x-card>'), LIBXML_NOERROR);

    $card = $document->querySelector('[data-flux-card]');
    $body = $document->querySelector('[data-flux-card-body]');

    expect([$card?->classList->contains('mt-6'), $card?->classList->contains('grid')])->toBe([true, false])
        ->and([$body?->classList->contains('grid'), $body?->classList->contains('mt-6')])->toBe([true, false]);
});

test('the Flux card props reach the card', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-card variant="muted" body="divided" size="sm">Content</x-card>'), LIBXML_NOERROR);

    $card = $document->querySelector('[data-flux-card]');

    expect([$card?->getAttribute('data-flux-card-variant'), $card?->getAttribute('data-flux-card-body-variant'), $card?->getAttribute('data-flux-card-size')])->toBe(['muted', 'divided', 'sm']);
});

test('a card without accordion has no fold behavior and no toggle', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-card heading="Profile">Content<x-slot:footer>Saved.</x-slot:footer></x-card>'), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-card]')?->hasAttribute('x-data'))->toBeFalse()
        ->and($document->querySelector('[data-flux-card-heading] button'))->toBeNull();
});

test('an accordion card puts an open toggle button in its heading', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-card accordion heading="Address"><p>Street</p><x-slot:footer>Saved.</x-slot:footer></x-card>'), LIBXML_NOERROR);

    $card = $document->querySelector('[data-flux-card]');
    $button = $document->querySelector('[data-flux-card-header] > [data-flux-card-heading] > button[data-card-toggle]');

    expect([$card?->getAttribute('x-data'), $card?->getAttribute('data-card-collapsed')])->toBe(['card', 'false'])
        ->and([trim($button?->textContent ?? ''), $button?->getAttribute('type'), $button?->getAttribute('aria-expanded')])->toBe(['Address', 'button', 'true'])
        ->and($document->querySelectorAll('[data-flux-card-body][hidden], [data-flux-card-footer][hidden]')->length)->toBe(0);
});

test('an accordion card renders the same HTML each time, so Livewire keeps its parts', function (): void {
    $template = '<x-card accordion heading="Address"><p>Street</p><x-slot:footer>Saved.</x-slot:footer></x-card>';

    expect((string) $this->blade($template))->toBe((string) $this->blade($template));
});

test('a collapsed accordion card is folded in the server HTML, where find in page can still reach its parts', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-card accordion collapsed heading="Address"><p>Street</p><x-slot:footer>Saved.</x-slot:footer></x-card>'), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-card]')?->getAttribute('data-card-collapsed'))->toBe('true')
        ->and($document->querySelector('[data-flux-card-heading] > button')?->getAttribute('aria-expanded'))->toBe('false')
        ->and($document->querySelectorAll('[data-flux-card-body][hidden="until-found"], [data-flux-card-footer][hidden="until-found"]')->length)->toBe(2);
});

test('an accordion card rejects an x-data of its own', function (): void {
    expect(fn () => $this->blade('<x-card accordion heading="Address" x-data="{ editing: false }">Street</x-card>'))
        ->toThrow(ViewException::class, 'An accordion card has its own x-data.');
});

test('the actions of an accordion card sit outside its toggle button', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade(
        '<x-card accordion heading="Address"><x-slot:actions><button type="button" data-test="edit">Edit</button></x-slot:actions>Street</x-card>',
    ), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-card-heading] > button'))->not->toBeNull()
        ->and($document->querySelector('[data-flux-card-header] > [data-flux-card-actions] > [data-test="edit"]'))->not->toBeNull()
        ->and($document->querySelector('[data-flux-card-heading] [data-test="edit"]'))->toBeNull();
});

test('a card inside an accordion card stays plain', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade(
        '<x-card accordion heading="Addresses"><x-card heading="Home">Street</x-card></x-card>',
    ), LIBXML_NOERROR);

    expect($document->querySelectorAll('[data-flux-card-heading] > button')->length)->toBe(1)
        ->and(trim($document->querySelector('[data-flux-card-heading] > button')?->textContent ?? ''))->toBe('Addresses');
});

test('an accordion card without a heading renders as a plain card', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-card accordion :heading="null" collapsed>Street</x-card>'), LIBXML_NOERROR);

    expect($document->querySelector('[data-flux-card]')?->hasAttribute('x-data'))->toBeFalse()
        ->and($document->querySelector('[data-flux-card-body]')?->hasAttribute('hidden'))->toBeFalse();
});
