<?php

declare(strict_types=1);

function mapScript(string $body): string
{
    $helpers = <<<'SCRIPT'
        const mapOf = (model) => fieldElement(model).closest('[x-data="map"]');
        const ready = (model) => mapOf(model).querySelector('.leaflet-container') !== null;
        const markerOf = (model) => mapOf(model).querySelector('.leaflet-marker-icon');
        SCRIPT;

    return browserScript("{$helpers}\n{$body}");
}

test('a click on the map sets the location and the inputs show it', function (): void {
    $page = visit('/docs/components/map');

    $page->script(mapScript("await until(() => ready('form.latitude'));"));
    $page->click("[data-map*='form.latitude'] [data-map-canvas]");

    expect($page->script(mapScript(<<<'SCRIPT'
        await until(() => markerOf('form.latitude') !== null);

        const latitude = property('form.latitude');
        const longitude = property('form.longitude');

        return Math.abs(latitude - 4.2) < 1
            && Math.abs(longitude - 109) < 1
            && Number(control('form.latitude').value) === latitude
            && Number(control('form.longitude').value) === longitude;
        SCRIPT)))->toBeTrue();
});

test('the marker follows typed values when the map is not clickable', function (): void {
    $page = visit('/docs/components/map');

    expect($page->script(mapScript(<<<'SCRIPT'
        await until(() => ready('form.site_latitude'));
        await setProperty('form.site_latitude', 1.4927);
        await setProperty('form.site_longitude', 103.7414);

        return await until(() => markerOf('form.site_latitude') !== null);
        SCRIPT)))->toBeTrue();
});

test('a click on a disabled map changes nothing', function (): void {
    $page = visit('/docs/components/map');

    $page->script(mapScript("await until(() => ready('form.depot_latitude'));"));
    $page->click("[data-map*='form.depot_latitude'] [data-map-canvas]");

    expect($page->script(mapScript(<<<'SCRIPT'
        await new Promise((resolve) => setTimeout(resolve, 300));

        return property('form.depot_latitude') == null && markerOf('form.depot_latitude') === null;
        SCRIPT)))->toBeTrue();
});

test('a map in a folded card centers the location when the card opens', function (): void {
    $page = visit('/docs/components/map');

    expect($page->script(mapScript(<<<'SCRIPT'
        await until(() => ready('form.branch_latitude'));
        await setProperty('form.branch_latitude', 1.4927);
        await setProperty('form.branch_longitude', 103.7414);

        mapOf('form.branch_latitude').closest('[data-flux-card]').querySelector('[data-card-toggle]').click();
        await until(() => markerOf('form.branch_latitude')?.getBoundingClientRect().width > 0);
        await new Promise((resolve) => setTimeout(resolve, 400));

        const canvas = mapOf('form.branch_latitude').querySelector('[data-map-canvas]').getBoundingClientRect();
        const marker = markerOf('form.branch_latitude').getBoundingClientRect();

        return Math.abs(marker.left + marker.width / 2 - (canvas.left + canvas.width / 2)) < 2
            && Math.abs(marker.bottom - (canvas.top + canvas.height / 2)) < 2;
        SCRIPT)))->toBeTrue();
});

test('a map that becomes disabled after an update ignores clicks and drags', function (): void {
    $page = visit('/docs/components/map');

    $page->script(mapScript(<<<'SCRIPT'
        await until(() => ready('form.latitude'));
        await setProperty('form.latitude', 4.2);
        await setProperty('form.longitude', 109);
        await until(() => markerOf('form.latitude') !== null);

        const root = mapOf('form.latitude');
        root.dataset.map = JSON.stringify({ ...JSON.parse(root.dataset.map), clickable: false });
        await tick();
        SCRIPT));
    $page->click("[data-map*='form.latitude'] [data-map-canvas]");

    expect($page->script(mapScript(<<<'SCRIPT'
        await new Promise((resolve) => setTimeout(resolve, 300));

        return [property('form.latitude'), property('form.longitude'), markerOf('form.latitude').classList.contains('leaflet-marker-draggable')];
        SCRIPT)))->toBe([4.2, 109, false]);
});
