<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\NoticeModal;

test('a modal starts closed, opens on the show event, and closes', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(NoticeModal::class)
        ->assertSet('open', false)
        ->dispatch('show')
        ->assertSet('open', true)
        ->call('close')
        ->assertSet('open', false);
});

test('opening a modal clears the errors of the last time', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(NoticeModal::class)
        ->call('fail')
        ->assertHasErrors('notice')
        ->call('show')
        ->assertHasNoErrors();
});

test('the browser can close a modal but cannot open it', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(NoticeModal::class)
        ->set('open', true)
        ->assertSet('open', false)
        ->call('show')
        ->set('open', false)
        ->assertSet('open', false);
});
