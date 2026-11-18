<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Models\Item;

beforeEach(function (): void {
    Schema::create('items', function (Blueprint $table): void {
        $table->id();
        $table->string('group');
        $table->string('name');
        $table->string('secret')->nullable();
        $table->unsignedInteger('sort_order');
        $table->timestamps();
        $table->softDeletes();
    });

    Relation::morphMap(['item' => Item::class]);
});

test('a malformed key is not found and runs no query', function (?string $field, string $value): void {
    $queries = collect();
    DB::listen(fn (QueryExecuted $query) => $queries->push($query->sql));

    expect(fn (): ?Item => (new Item)->resolveRouteBinding($value, $field))->toThrow(ModelNotFoundException::class);

    expect($queries)->toBeEmpty();
})->with([
    'the route key' => [null],
    'the key with its table' => ['items.id'],
])->with([
    'letters' => ['abc'],
    'more digits than a bigint holds' => ['99999999999999999999'],
]);

test('a value for a column that is not the key still runs the query', function (): void {
    $queries = collect();
    DB::listen(fn (QueryExecuted $query) => $queries->push($query->sql));

    $item = (new Item)->resolveRouteBinding('abc', 'name');

    expect($item)->toBeNull()
        ->and($queries)->toHaveCount(1);
});

test('a valid key finds the record', function (): void {
    $item = Item::query()->create(['group' => 'a', 'name' => 'First']);

    $found = (new Item)->resolveRouteBinding((string) $item->id);

    expect($found?->getKey())->toBe($item->id);
});
