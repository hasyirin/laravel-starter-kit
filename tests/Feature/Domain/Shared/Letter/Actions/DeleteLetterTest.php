<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Actions\DeleteLetter;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Organization\Models\Organization;
use Tests\Fixtures\Support\NoticeLetter;

test('deleting a letter soft deletes it', function (): void {
    config(['system.letters.types' => ['notice' => NoticeLetter::class]]);
    $letter = Letter::factory()->create();

    DeleteLetter::make()->execute($letter);

    expect(Letter::query()->find($letter->id))->toBeNull()
        ->and(Letter::withTrashed()->find($letter->id)?->trashed())->toBeTrue()
        ->and(Organization::query()->count())->toBe(1);
});
