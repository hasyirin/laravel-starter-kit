<?php

declare(strict_types=1);

use App\Livewire\Pages\Local\Examples\TabBarType;
use App\Livewire\Pages\Local\Examples\Tabs;
use Illuminate\View\ViewException;
use Livewire\Livewire;

test('the bar comes as the text of a tag or as an enum case', function (array $params, string $marker): void {
    Livewire::test(Tabs::class, $params)
        ->assertSeeHtml($marker);
})->with([
    'card as text' => [['bar' => 'card', 'strip' => true], 'data-tabs-strip'],
    'navlist as text' => [['bar' => 'navlist'], 'data-flux-navlist'],
    'navbar as text' => [['bar' => 'navbar'], 'data-flux-navbar'],
    'an enum case' => [['bar' => TabBarType::Navlist], 'data-flux-navlist'],
]);

test('an unknown bar fails at once', function (): void {
    expect(fn () => Livewire::test(Tabs::class, ['bar' => 'navlsit']))
        ->toThrow(ViewException::class, '"navlsit" is not a valid backing value for enum');
});

test('the bar stays an enum case on the next request', function (): void {
    Livewire::test(Tabs::class, ['bar' => 'navlist'])
        ->call('switchTab', 'notes')
        ->assertSet('bar', TabBarType::Navlist);
});
