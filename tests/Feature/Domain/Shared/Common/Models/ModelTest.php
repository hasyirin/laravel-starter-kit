<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
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

test('sort order continues from the highest sibling in the same group', function (): void {
    Item::query()->create(['group' => 'a', 'name' => 'First']);
    Item::query()->create(['group' => 'a', 'name' => 'Second']);
    Item::query()->create(['group' => 'b', 'name' => 'Other']);

    expect(Item::query()->where('group', 'a')->pluck('sort_order')->all())->toBe([1, 2])
        ->and(Item::query()->where('group', 'b')->value('sort_order'))->toBe(1);
});

test('an explicit sort order is kept', function (): void {
    $item = Item::query()->create(['group' => 'a', 'name' => 'Pinned', 'sort_order' => 10]);

    expect($item->sort_order)->toBe(10);
});

test('the activity log records fillable changes but not hidden ones', function (): void {
    $item = Item::query()->create(['group' => 'a', 'name' => 'First', 'secret' => 'hunter2']);

    $item->update(['name' => 'Renamed', 'secret' => 'changed']);

    $changes = Activity::query()->where('event', 'updated')->sole()->attribute_changes;

    expect($changes?->get('attributes'))->toBe(['name' => 'Renamed'])
        ->and($changes?->get('old'))->toBe(['name' => 'First']);
});

test('timestamps are formatted with the configured datetime format', function (): void {
    $this->travelTo('2026-10-18 14:30:00');

    $item = Item::query()->create(['group' => 'a', 'name' => 'First']);

    expect($item->formatted_created_at)->toBe('18/10/2026, 02:30 PM')
        ->and($item->formatted_updated_at)->toBe('18/10/2026, 02:30 PM');
});
