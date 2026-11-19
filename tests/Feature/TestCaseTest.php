<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

/**
 * The test below depends on this one. This page leaves a stale entry in the Livewire render list, and the next test in the same worker must not see it.
 */
test('the security tab aborts its render when the password is not confirmed', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('settings', ['tab' => 'security']))
        ->assertRedirect(route('password.confirm'));

    expect(ExtendBlade::isRenderingLivewireComponent())->toBeTrue();
});

test('a Blade condition that is false renders nothing after a test that aborted a render', function (): void {
    expect(trim((string) $this->blade('@if(false) hidden @endif')))->toBeEmpty();
});
