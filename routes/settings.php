<?php

declare(strict_types=1);

use App\Http\Enums\Area;
use App\Livewire\Settings\Index;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::livewire('settings', Index::class)->name('settings');

Route::get('settings/user-information', fn (): RedirectResponse => to_route(Area::current()->route('settings')))->name('user-information.edit');

Route::get('settings/preferences', fn (): RedirectResponse => to_route(Area::current()->route('settings'), ['tab' => 'preferences']))->name('preferences.edit');

Route::get('settings/security', fn (): RedirectResponse => to_route(Area::current()->route('settings'), ['tab' => 'security']))->name('security.edit');

Route::get('settings/appearance', fn (): RedirectResponse => to_route(Area::current()->route('settings'), ['tab' => 'preferences']))->name('appearance.edit');
