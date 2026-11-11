<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\SavePostOffice;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;

test('saving creates and updates a city of a state', function (): void {
    $state = State::factory()->create();

    $postOffice = SavePostOffice::make()->execute($state, new PostOffice, 'Muar');

    expect($postOffice->state_id)->toBe($state->id);

    SavePostOffice::make()->execute($state, $postOffice, 'Bandar Maharani');

    expect($postOffice->fresh()->name)->toBe('Bandar Maharani');
});

test('creating a city with the name of a trashed city of the same state restores that city', function (): void {
    $state = State::factory()->create();
    $trashed = PostOffice::factory()->for($state)->create(['name' => 'Muar']);
    $trashed->delete();

    $postOffice = SavePostOffice::make()->execute($state, new PostOffice, 'Muar');

    expect($postOffice->id)->toBe($trashed->id)
        ->and($postOffice->fresh()->trashed())->toBeFalse()
        ->and(PostOffice::withTrashed()->count())->toBe(1);
});
