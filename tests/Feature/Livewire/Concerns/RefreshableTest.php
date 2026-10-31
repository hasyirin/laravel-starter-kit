<?php

declare(strict_types=1);

use Livewire\Livewire;
use Tests\Fixtures\Livewire\RenderCounter;

test('a component renders again on the refresh event', function (): void {
    Livewire::test(RenderCounter::class)
        ->assertSeeText('Renders: 1')
        ->dispatch('refresh')
        ->assertSeeText('Renders: 2');
});
