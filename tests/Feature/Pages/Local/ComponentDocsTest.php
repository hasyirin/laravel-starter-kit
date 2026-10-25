<?php

declare(strict_types=1);

$pages = collect(glob(dirname(__DIR__, 4).'/docs/components/*.md') ?: [])
    ->map(fn (string $path): string => basename($path, '.md'))
    ->reject(fn (string $page): bool => $page === 'README')
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
