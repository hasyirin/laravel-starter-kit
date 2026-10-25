<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Livewire\Pages\App\Dashboard;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSeeLivewire(Dashboard::class)
        ->assertSee('<title>'.__('navigation.dashboard').' - '.config('app.name').'</title>', false);
});
