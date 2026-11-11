<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\MessageLogDataTable;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->viewer = User::factory()->create();
    grantPermissions($this->viewer, 'system.access', 'system.logs.messages.view');
    $this->actingAs($this->viewer);
});

test('the list of a record shows only the emails about that record', function (): void {
    $letter = User::factory()->create();
    $about = Message::factory()->create();
    $about->resource()->associate($letter)->save();
    $other = Message::factory()->create();

    Livewire::test(MessageLogDataTable::class, ['resource' => $letter])
        ->assertCanSeeTableRecords([$about])
        ->assertCanNotSeeTableRecords([$other]);
});
