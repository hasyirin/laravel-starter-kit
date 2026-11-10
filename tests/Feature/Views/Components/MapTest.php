<?php

declare(strict_types=1);

use Dom\HTMLDocument;
use Illuminate\View\ViewException;

function mapDocument(string $template): HTMLDocument
{
    return HTMLDocument::createFromString((string) test()->blade($template), LIBXML_NOERROR);
}

/**
 * @return array<string, mixed>
 */
function mapOptions(HTMLDocument $document): array
{
    return json_decode((string) $document->querySelector('[x-data="map"]')?->getAttribute('data-map'), true);
}

test('a map binds its latitude and longitude inputs and lets the user click', function (): void {
    $document = mapDocument('<x-map latitude-model="form.latitude" longitude-model="form.longitude" />');
    $options = mapOptions($document);

    expect($document->querySelector('input[wire\:model="form.latitude"]'))->not->toBeNull()
        ->and($document->querySelector('input[wire\:model="form.longitude"]'))->not->toBeNull()
        ->and($document->querySelector('[data-input-control][readonly]'))->toBeNull()
        ->and($document->querySelector('[data-map-canvas]')?->hasAttribute('wire:ignore'))->toBeTrue()
        ->and($options)->latitudeModel->toBe('form.latitude')->longitudeModel->toBe('form.longitude')->clickable->toBeTrue();
});

test('the map takes its center, zoom, and translated layers from the config', function (): void {
    config([
        'map.center' => ['latitude' => 2.0442, 'longitude' => 102.5689],
        'map.zoom' => 9,
        'map.layers' => [
            'street' => ['label' => 'common/map.street', 'url' => 'https://tiles.test/{z}/{x}/{y}.png', 'attribution' => 'Tiles'],
        ],
    ]);

    $options = mapOptions(mapDocument('<x-map latitude-model="form.latitude" longitude-model="form.longitude" />'));

    expect($options)->center->toBe([2.0442, 102.5689])->zoom->toBe(9)
        ->and($options['layers'])->toBe([
            ['label' => 'Street', 'url' => 'https://tiles.test/{z}/{x}/{y}.png', 'attribution' => 'Tiles', 'maxZoom' => 19, 'subdomains' => null],
        ]);
});

test('a map that is not clickable keeps its inputs editable', function (): void {
    $document = mapDocument('<x-map latitude-model="form.latitude" longitude-model="form.longitude" :clickable="false" />');

    expect(mapOptions($document)['clickable'])->toBeFalse()
        ->and($document->querySelector('[data-input-control][readonly]'))->toBeNull();
});

test('a map without editable inputs makes them read-only', function (): void {
    $document = mapDocument('<x-map latitude-model="form.latitude" longitude-model="form.longitude" :editable-inputs="false" />');

    expect(mapOptions($document)['clickable'])->toBeTrue()
        ->and($document->querySelectorAll('[data-input-control][readonly]'))->toHaveCount(2);
});

test('a disabled card disables the map and its inputs', function (): void {
    $document = mapDocument('<x-card disabled><x-map latitude-model="form.latitude" longitude-model="form.longitude" /></x-card>');

    expect(mapOptions($document)['clickable'])->toBeFalse()
        ->and($document->querySelectorAll('[data-input-control][disabled]'))->toHaveCount(2);
});

test('a map with plain values shows them and cannot change them', function (): void {
    $document = mapDocument('<x-map :latitude="2.0442" :longitude="102.5689" />');
    $options = mapOptions($document);

    expect($options)->latitude->toBe(2.0442)->longitude->toBe(102.5689)->clickable->toBeFalse()->latitudeModel->toBeNull()
        ->and($document->querySelectorAll('[data-input-control][readonly]'))->toHaveCount(2)
        ->and($document->querySelector('[wire\:model]'))->toBeNull();
});

test('a map without layers fails at once', function (): void {
    config(['map.layers' => []]);

    expect(fn (): string => (string) $this->blade('<x-map latitude-model="form.latitude" longitude-model="form.longitude" />'))
        ->toThrow(ViewException::class, 'Set at least one layer in map.layers in config/map.php.');
});
