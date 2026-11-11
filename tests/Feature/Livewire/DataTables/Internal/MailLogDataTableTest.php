<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Models\Mail;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\MailLogDataTable;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->viewer = User::factory()->create();
    grantPermissions($this->viewer, 'system.access', 'system.logs.mail.view');
    $this->actingAs($this->viewer);
});

test('the list of a record shows only the emails about that record', function (): void {
    $letter = User::factory()->create();
    $about = Mail::factory()->create();
    $about->resource()->associate($letter)->save();
    $other = Mail::factory()->create();

    Livewire::test(MailLogDataTable::class, ['resource' => $letter])
        ->assertCanSeeTableRecords([$about])
        ->assertCanNotSeeTableRecords([$other]);
});
