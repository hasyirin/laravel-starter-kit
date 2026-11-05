<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Livewire\Layouts\Local\Sidebar;
use Dom\HTMLDocument;

$pages = collect(glob(dirname(__DIR__, 4).'/docs/components/*.md') ?: [])
    ->map(fn (string $path): string => basename($path, '.md'))
    ->reject(fn (string $page): bool => $page === 'README')
    ->sort()
    ->values()
    ->all();

test('the component docs are not found outside the local environment', function (): void {
    $response = $this->get(route('docs.components'));

    $response->assertNotFound();
});

test('the component docs link to every component page', function () use ($pages): void {
    app()->detectEnvironment(fn (): string => 'local');

    $response = $this->get(route('docs.components'));

    $response->assertOk()->assertSeeInOrder(array_map(fn (string $page): string => route('docs.components', $page), $pages), false);
});

test('a component page renders every example in its file', function (string $page): void {
    app()->detectEnvironment(fn (): string => 'local');

    $response = $this->get(route('docs.components', $page));

    expect(substr_count((string) $response->assertOk()->getContent(), 'data-docs-example'))
        ->toBe(substr_count((string) file_get_contents(base_path("docs/components/{$page}.md")), '```blade'));
})->with($pages);

test('an unknown component page is not found', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $response = $this->get(route('docs.components', 'missing'));

    $response->assertNotFound();
});

test('the component docs show the local sidebar without a user menu to guests', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $response = $this->get(route('docs.components', 'input'));

    $response->assertOk()
        ->assertSeeLivewire(Sidebar::class)
        ->assertDontSeeHtml('data-test="user-menu-button"');

    $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    expect($document->querySelector('a[data-flux-sidebar-item][data-current][href="'.route('docs.components', 'input').'"]'))->not->toBeNull();
});

test('the component docs show the user menu to a signed-in user', function (): void {
    app()->detectEnvironment(fn (): string => 'local');
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('docs.components'));

    $response->assertOk()->assertSeeHtml('data-test="user-menu-button"');
});
