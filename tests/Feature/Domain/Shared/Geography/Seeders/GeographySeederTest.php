<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Seeders\GeographySeeder;
use Illuminate\Database\Eloquent\Builder;

test('the geography seeder fills the place tables', function (): void {
    $this->seed(GeographySeeder::class);

    $this->assertDatabaseCount(State::class, 16);
    expect(State::query()->doesntHave('districts')->pluck('name')->all())->toBeEmpty();
    expect(PostOffice::query()->whereHas('postcodes', fn (Builder $query) => $query->where('code', '84300'))->orderBy('name')->pluck('name')->all())
        ->toBe(['Bukit Pasir', 'Muar']);
});

test('seeding the geography again writes nothing', function (): void {
    $this->seed(GeographySeeder::class);
    $updatedAt = Postcode::query()->max('updated_at');
    $count = Postcode::query()->count();

    $this->travel(1)->minute();
    $this->seed(GeographySeeder::class);

    expect(Postcode::query()->max('updated_at'))->toBe($updatedAt);
    expect(Postcode::query()->count())->toBe($count);
});
