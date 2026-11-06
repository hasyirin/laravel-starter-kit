<?php

declare(strict_types=1);

use Dom\Element;
use Dom\HTMLDocument;

function breadcrumbs(string $blade, array $data = []): HTMLDocument
{
    return HTMLDocument::createFromString((string) test()->blade($blade, $data), LIBXML_NOERROR);
}

function breadcrumbTexts(HTMLDocument $document): array
{
    $items = iterator_to_array($document->querySelectorAll('nav > [data-flux-breadcrumbs] > [data-flux-breadcrumbs-item]'));

    return array_map(fn (Element $item): string => trim($item->textContent), $items);
}

function trailOf(int $count): array
{
    return array_map(fn (int $number): array => ['label' => "Level {$number}", 'href' => "/level/{$number}"], range(1, $count));
}

test('a trail is a labelled nav that starts at the home route and ends at the current page', function (): void {
    $document = breadcrumbs('<x-breadcrumbs :items="[[\'label\' => \'States\', \'href\' => \'/states\'], [\'label\' => \'Penang\']]" />');

    expect($document->querySelector('nav')?->getAttribute('aria-label'))->toBe(__('navigation.breadcrumbs.label'))
        ->and($document->querySelector('nav a')?->getAttribute('href'))->toBe(route('home'))
        ->and($document->querySelector('nav a')?->getAttribute('aria-label'))->toBe(__('navigation.breadcrumbs.home'))
        ->and(breadcrumbTexts($document))->toBe(['', 'States', 'Penang'])
        ->and(trim($document->querySelector('[aria-current="page"]')?->textContent ?? ''))->toBe('Penang');
});

test('an item with a link navigates without a reload and an item without one is text', function (): void {
    $document = breadcrumbs('<x-breadcrumbs :items="[[\'label\' => \'States\', \'href\' => \'/states\'], [\'label\' => \'North\'], [\'label\' => \'Penang\']]" />');

    expect($document->querySelector('a[href="/states"]')?->hasAttribute('wire:navigate'))->toBeTrue()
        ->and($document->querySelectorAll('nav a'))->toHaveCount(2);
});

test('the current page is never a link', function (): void {
    $document = breadcrumbs('<x-breadcrumbs :items="[[\'label\' => \'Penang\', \'href\' => \'/penang\']]" />');

    expect($document->querySelector('a[href="/penang"]'))->toBeNull()
        ->and(trim($document->querySelector('[aria-current="page"]')?->textContent ?? ''))->toBe('Penang');
});

test('the home link can go elsewhere or be left out', function (): void {
    $items = [['label' => 'Penang']];

    expect(breadcrumbs('<x-breadcrumbs :$items home="/system" />', ['items' => $items])->querySelector('nav a')?->getAttribute('href'))->toBe('/system')
        ->and(breadcrumbs('<x-breadcrumbs :$items :home="false" />', ['items' => $items])->querySelector('nav a'))->toBeNull();
});

test('a null item is skipped', function (): void {
    $items = [null, ['label' => 'Penang']];

    expect(breadcrumbTexts(breadcrumbs('<x-breadcrumbs :$items :home="false" />', ['items' => $items])))->toBe(['Penang']);
});

test('a trail without items renders nothing', function (): void {
    expect(trim((string) $this->blade('<x-breadcrumbs :items="[]" />')))->toBeEmpty();
});

test('a trail at the limit shows every item', function (): void {
    $document = breadcrumbs('<x-breadcrumbs :items="$items" :home="false" />', ['items' => trailOf(4)]);

    expect(breadcrumbTexts($document))->toBe(['Level 1', 'Level 2', 'Level 3', 'Level 4'])
        ->and($document->querySelector('ui-dropdown'))->toBeNull();
});

test('a trail over the limit keeps the parent and the current page and moves the rest to a dropdown', function (): void {
    $document = breadcrumbs('<x-breadcrumbs :items="$items" />', ['items' => trailOf(6)]);
    $dropdown = $document->querySelector('ui-dropdown');
    $hidden = array_map(fn (Element $link): string => $link->getAttribute('href'), iterator_to_array($dropdown->querySelectorAll('a[wire\\:navigate]')));
    $texts = breadcrumbTexts($document);

    expect($hidden)->toBe(['/level/1', '/level/2', '/level/3', '/level/4'])
        ->and(array_slice($texts, -2))->toBe(['Level 5', 'Level 6'])
        ->and($texts)->toHaveCount(4)
        ->and($dropdown->querySelector('button')?->getAttribute('aria-label'))->toBe(__('navigation.breadcrumbs.more'));
});

test('a page can set its own limit', function (): void {
    $document = breadcrumbs('<x-breadcrumbs :items="$items" :max="2" />', ['items' => trailOf(3)]);

    expect($document->querySelectorAll('ui-dropdown a'))->toHaveCount(1);
});

test('a hidden item without a link is marked as disabled in the dropdown', function (): void {
    $items = [['label' => 'North'], ...trailOf(2)];

    $document = breadcrumbs('<x-breadcrumbs :$items :max="2" />', ['items' => $items]);

    expect(trim($document->querySelector('ui-dropdown [aria-disabled="true"]')?->textContent ?? ''))->toBe('North');
});
