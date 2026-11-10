<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Livewire\Components\Navigation\Style;
use Dom\HTMLDocument;

test('the app layout puts the navigation in a sidebar by default', function (): void {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));

    $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    expect($document->querySelector('ui-sidebar[collapsible="true"] [data-flux-sidebar-item][href="'.route('dashboard').'"]'))->not->toBeNull();
    expect($document->querySelector('[data-flux-header] [data-flux-navbar-items]'))->toBeNull();
});

test('the app layout puts the navigation in a top bar when the config asks for it', function (): void {
    config(['system.navigation.app' => Style::Topbar]);
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));

    $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    expect($document->querySelector('[data-flux-header] [data-flux-navbar-items][data-current][href="'.route('dashboard').'"]'))->not->toBeNull();
});

test('the top bar layout keeps the navigation in a drawer for small screens', function (): void {
    config(['system.navigation.app' => Style::Topbar]);
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));

    $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    expect($document->querySelector('ui-sidebar[collapsible="mobile"] [data-flux-sidebar-item][href="'.route('dashboard').'"]'))->not->toBeNull();
});
