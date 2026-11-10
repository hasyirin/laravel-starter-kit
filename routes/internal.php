<?php

declare(strict_types=1);

use App\Livewire\Pages\Internal\Controls\Organization\Branch\Create as BranchCreate;
use App\Livewire\Pages\Internal\Controls\Organization\Branch\Show as BranchShow;
use App\Livewire\Pages\Internal\Controls\Organization\Create as OrganizationCreate;
use App\Livewire\Pages\Internal\Controls\Organization\Department\Create as DepartmentCreate;
use App\Livewire\Pages\Internal\Controls\Organization\Department\Show as DepartmentShow;
use App\Livewire\Pages\Internal\Controls\Organization\Index as OrganizationIndex;
use App\Livewire\Pages\Internal\Controls\Organization\Show as OrganizationShow;
use App\Livewire\Pages\Internal\Controls\Role\Create as RoleCreate;
use App\Livewire\Pages\Internal\Controls\Role\Index as RoleIndex;
use App\Livewire\Pages\Internal\Controls\Role\Show as RoleShow;
use App\Livewire\Pages\Internal\Controls\User\Create;
use App\Livewire\Pages\Internal\Controls\User\Index;
use App\Livewire\Pages\Internal\Controls\User\Show;
use App\Livewire\Pages\Internal\Dashboard;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Dashboard::class)->name('dashboard');

Route::prefix('controls')->name('controls.')->group(function (): void {
    Route::livewire('users', Index::class)->name('users.index');
    Route::livewire('users/create', Create::class)->name('users.create');
    Route::livewire('users/{user}', Show::class)->name('users.show');

    Route::livewire('roles', RoleIndex::class)->name('roles.index');
    Route::livewire('roles/create', RoleCreate::class)->name('roles.create');
    Route::livewire('roles/{role}', RoleShow::class)->name('roles.show');
    Route::livewire('roles/{role}/users/{user}', Show::class)->name('roles.users.show');

    Route::livewire('organizations', OrganizationIndex::class)->name('organizations.index');
    Route::livewire('organizations/create', OrganizationCreate::class)->name('organizations.create');
    Route::livewire('organizations/{organization}', OrganizationShow::class)->name('organizations.show');
    Route::livewire('organizations/{organization}/users/{user}', Show::class)->name('organizations.users.show');
    Route::livewire('organizations/{organization}/departments/create', DepartmentCreate::class)->name('organizations.departments.create');
    Route::livewire('organizations/{organization}/departments/{department}', DepartmentShow::class)->name('organizations.departments.show')->scopeBindings();
    Route::livewire('organizations/{organization}/departments/{department}/users/{user}', Show::class)->name('organizations.departments.users.show');
    Route::livewire('organizations/{organization}/branches/create', BranchCreate::class)->name('organizations.branches.create');
    Route::livewire('organizations/{organization}/branches/{branch}', BranchShow::class)->name('organizations.branches.show')->scopeBindings();
    Route::livewire('organizations/{organization}/branches/{branch}/users/{user}', Show::class)->name('organizations.branches.users.show');
});

Route::redirect('settings', '/internal/settings/user-information')->name('settings');

require __DIR__.'/settings.php';
