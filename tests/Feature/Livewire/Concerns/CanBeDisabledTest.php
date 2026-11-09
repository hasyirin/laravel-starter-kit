<?php

declare(strict_types=1);

use Livewire\Features\SupportReactiveProps\CannotMutateReactivePropException;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\ArchivedLockable;
use Tests\Fixtures\Livewire\Lockable;

test('a component is enabled unless the parent disables it', function (bool $disabled): void {
    $component = Livewire::test(Lockable::class, ['disabled' => $disabled]);

    expect($component->instance()->isDisabled())->toBe($disabled);
    $component->assertViewHas('disabled', $disabled)
        ->assertSeeText($disabled ? 'Read only' : 'Editable');
})->with([
    'enabled' => [false],
    'disabled' => [true],
]);

test('the browser cannot turn off the disabled prop', function (): void {
    Livewire::test(Lockable::class, ['disabled' => true])->set('disabled', false);
})->throws(CannotMutateReactivePropException::class);

test('a component can disable itself with its own rule', function (): void {
    Livewire::test(ArchivedLockable::class)
        ->assertViewHas('disabled', true)
        ->call('save')
        ->assertForbidden();
});

test('only an enabled component runs its actions', function (bool $disabled): void {
    $component = Livewire::test(Lockable::class, ['disabled' => $disabled])->call('save');

    if ($disabled) {
        $component->assertForbidden();
    } else {
        $component->assertSet('saved', true);
    }
})->with([
    'enabled' => [false],
    'disabled' => [true],
]);
