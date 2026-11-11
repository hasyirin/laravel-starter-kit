<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\DeletePostcode;
use App\Domain\Shared\Geography\Models\Postcode;

test('deleting a postcode soft-deletes only that postcode', function (): void {
    $postcode = Postcode::factory()->create();

    DeletePostcode::make()->execute($postcode);

    expect($postcode->fresh()->trashed())->toBeTrue()
        ->and(Postcode::withTrashed()->count())->toBe(1);
});
