<?php

declare(strict_types=1);

use Dom\HTMLDocument;
use Dom\Node;
use Illuminate\View\ViewException;
use Livewire\Component;
use Livewire\Livewire;

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

function mapLocationText(HTMLDocument $document): string
{
    return str($document->querySelector('[data-map-location]')?->textContent)->squish()->value();
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

test('a map without editable inputs shows the location as a line instead of inputs', function (): void {
    $document = mapDocument('<x-map latitude-model="form.latitude" longitude-model="form.longitude" :editable-inputs="false" />');

    expect(mapOptions($document)['clickable'])->toBeTrue()
        ->and($document->querySelector('[x-data="map"] input'))->toBeNull()
        ->and($document->querySelector('[wire\\:model]'))->toBeNull()
        ->and($document->querySelector('[data-map-location]'))->not->toBeNull();
});

test('a map with inputs shows no location line', function (): void {
    $document = mapDocument('<x-map latitude-model="form.latitude" longitude-model="form.longitude" />');

    expect($document->querySelector('[data-map-location]'))->toBeNull();
});

test('a disabled card disables the map and its inputs', function (): void {
    $document = mapDocument('<x-card disabled><x-map latitude-model="form.latitude" longitude-model="form.longitude" /></x-card>');

    expect(mapOptions($document)['clickable'])->toBeFalse()
        ->and($document->querySelectorAll('[data-input-control][disabled]'))->toHaveCount(2);
});

test('a map with plain values shows them as a line and cannot change them', function (): void {
    $document = mapDocument('<x-map :latitude="2.0442" :longitude="102.5689" />');
    $options = mapOptions($document);

    expect($options)->latitude->toBe(2.0442)->longitude->toBe(102.5689)->clickable->toBeFalse()->latitudeModel->toBeNull()
        ->and($document->querySelector('[x-data="map"] input'))->toBeNull()
        ->and($document->querySelector('[wire\:model]'))->toBeNull()
        ->and(mapLocationText($document))->toBe('2.0442, 102.5689');
});

test('the location line drops trailing zeros and keeps at most 6 decimals', function (): void {
    expect(mapLocationText(mapDocument('<x-map latitude="2.500000" :longitude="102.1234567" />')))->toBe('2.5, 102.123457')
        ->and(mapLocationText(mapDocument('<x-map :latitude="3.0" :longitude="-73.9857" />')))->toBe('3, -73.9857');
});

test('a map with no location says so', function (): void {
    expect(mapLocationText(mapDocument('<x-map :latitude="null" :longitude="103.7414" />')))->toBe(__('common/inputs.no_location'))
        ->and(mapLocationText(mapDocument('<x-map latitude="north" :longitude="103.7414" />')))->toBe(__('common/inputs.no_location'));
});

test('the caption follows the location or no location on the same line', function (): void {
    expect(mapLocationText(mapDocument('<x-map :latitude="2.0442" :longitude="102.5689" caption="Set by Aminah" />')))->toBe('2.0442, 102.5689 · Set by Aminah')
        ->and(mapLocationText(mapDocument('<x-map caption="Set by Aminah" />')))->toBe(__('common/inputs.no_location').' · Set by Aminah')
        ->and(mapLocationText(mapDocument('<x-map latitude-model="form.latitude" longitude-model="form.longitude" :editable-inputs="false" caption="Set by Aminah" />')))->toEndWith(' · Set by Aminah');
});

test('a map with inputs ignores the caption', function (): void {
    $document = mapDocument('<x-map latitude-model="form.latitude" longitude-model="form.longitude" caption="Set by Aminah" />');

    expect($document->querySelector('[x-data="map"]')?->textContent)->not->toContain('Set by Aminah');
});

test('a map without inputs shows only the first error of the location', function (): void {
    $template = '<x-map latitude-model="form.latitude" longitude-model="form.longitude" :editable-inputs="false" />';

    $this->withViewErrors(['form.latitude' => 'The latitude is wrong.', 'form.longitude' => 'The longitude is wrong.']);
    $bothErrors = mapDocument($template)->querySelectorAll('[data-flux-error]');

    $this->withViewErrors(['form.longitude' => 'The longitude is wrong.']);
    $longitudeError = mapDocument($template)->querySelectorAll('[data-flux-error]');

    expect($bothErrors)->toHaveCount(1)
        ->and(trim($bothErrors->item(0)->textContent))->toBe('The latitude is wrong.')
        ->and($longitudeError)->toHaveCount(1)
        ->and(trim($longitudeError->item(0)->textContent))->toBe('The longitude is wrong.');
});

test('required marks only the map label', function (string $template): void {
    $marks = mapDocument($template)->querySelectorAll('[data-input-label] [aria-hidden="true"]');

    expect($marks)->toHaveCount(1)
        ->and(trim($marks->item(0)->closest('[data-input-label]')->querySelector('[data-flux-label]')->textContent))->toBe(__('common/inputs.location'));
})->with([
    'with inputs' => '<x-map latitude-model="form.latitude" longitude-model="form.longitude" required />',
    'without inputs' => '<x-map latitude-model="form.latitude" longitude-model="form.longitude" :editable-inputs="false" required />',
    'a saved location' => '<x-map :latitude="2.0442" :longitude="102.5689" required />',
]);

test('the inputs of a required map are not required in HTML', function (): void {
    $document = mapDocument('<x-map latitude-model="form.latitude" longitude-model="form.longitude" required />');

    expect($document->querySelectorAll('[x-data="map"] input'))->not->toBeEmpty()
        ->and($document->querySelector('[x-data="map"] input[required]'))->toBeNull();
});

test('a map shows the canvas, the location line, the error, and then the trailing description', function (): void {
    $this->withViewErrors(['form.latitude' => 'The latitude is wrong.']);

    $document = mapDocument('<x-map latitude-model="form.latitude" longitude-model="form.longitude" :editable-inputs="false" description="Pick the office." description:trailing="Drag the marker to move it." />');
    $descriptions = $document->querySelectorAll('[data-flux-description]');
    $canvas = $document->querySelector('[data-map-canvas]');
    $line = $document->querySelector('[data-map-location]');
    $error = $document->querySelector('[data-flux-error]');
    $trailing = $descriptions->item(1);

    expect($descriptions)->toHaveCount(2)
        ->and(trim($descriptions->item(0)->textContent))->toBe('Pick the office.')
        ->and(trim($trailing->textContent))->toBe('Drag the marker to move it.')
        ->and($canvas->compareDocumentPosition($line) & Node::DOCUMENT_POSITION_FOLLOWING)->toBeGreaterThan(0)
        ->and($line->compareDocumentPosition($error) & Node::DOCUMENT_POSITION_FOLLOWING)->toBeGreaterThan(0)
        ->and($error->compareDocumentPosition($trailing) & Node::DOCUMENT_POSITION_FOLLOWING)->toBeGreaterThan(0)
        ->and($document->querySelector('[description\:trailing]'))->toBeNull();
});

test('a map with models and without inputs shows the saved location before the page script runs', function (): void {
    $host = new class extends Component
    {
        /** @var array<string, mixed> */
        public array $form = [];

        public function render(): string
        {
            return '<div><x-map latitude-model="form.latitude" longitude-model="form.longitude" :editable-inputs="false" /></div>';
        }
    };

    $saved = HTMLDocument::createFromString(Livewire::test($host, ['form' => ['latitude' => '1.492700', 'longitude' => 103.7414]])->html(), LIBXML_NOERROR);
    $empty = HTMLDocument::createFromString(Livewire::test($host, ['form' => ['latitude' => null, 'longitude' => 103.7414]])->html(), LIBXML_NOERROR);

    expect(trim($saved->querySelector('[data-map-location] [x-text="location"]')->textContent))->toBe('1.4927, 103.7414')
        ->and($saved->querySelector('[data-map-location] [x-show]')->getAttribute('style'))->toContain('display: none')
        ->and(mapLocationText($empty))->toBe(__('common/inputs.no_location'))
        ->and((string) $empty->querySelector('[data-map-location] [x-show]')->getAttribute('style'))->not->toContain('display: none')
        ->and($empty->querySelector('[data-map-location] [x-show]')->hasAttribute('x-cloak'))->toBeFalse();
});

test('a map without layers fails at once', function (): void {
    config(['map.layers' => []]);

    expect(fn (): string => (string) $this->blade('<x-map latitude-model="form.latitude" longitude-model="form.longitude" />'))
        ->toThrow(ViewException::class, 'Set at least one layer in map.layers in config/map.php.');
});
