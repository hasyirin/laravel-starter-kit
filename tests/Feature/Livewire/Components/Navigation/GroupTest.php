<?php

declare(strict_types=1);

use App\Livewire\Components\Navigation\Group;
use App\Livewire\Components\Navigation\Item;

test('a group is accessible when any of its items is', function (): void {
    $group = Group::make('Reports', [
        Item::make(label: 'Payroll', accessible: false),
        Item::make(label: 'Sales'),
    ]);

    expect($group->accessible)->toBeTrue();
});

test('a group is hidden when none of its items is accessible', function (): void {
    $group = Group::make('Reports', [
        Item::make(label: 'Payroll', accessible: false),
    ]);

    expect($group->accessible)->toBeFalse();
});

test('a group keeps only the items the user may see', function (): void {
    $group = Group::make('Reports', [
        Item::make(label: 'Payroll', accessible: false),
        Item::make(label: 'Sales'),
    ]);

    expect($group->items->pluck('label')->all())->toBe(['Sales']);
});

test('a group renders its heading above its items', function (): void {
    $group = Group::make('Reports', [
        Item::make(label: 'Sales', href: 'https://example.com/sales'),
    ]);

    $view = $this->blade('{{ $group }}', ['group' => $group]);

    $view->assertSeeInOrder(['Reports', 'https://example.com/sales']);
});

test('a group rejects entries that are not items', function (): void {
    expect(fn (): Group => Group::make('Reports', ['Sales']))->toThrow(UnexpectedValueException::class);
});
