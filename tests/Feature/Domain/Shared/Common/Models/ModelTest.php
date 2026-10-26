<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
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

test('timestamps are formatted in the time zone and locale of the current user', function (): void {
    $this->travelTo('2026-10-18 14:30:00');
    context()->addHidden(['timezone' => 'Asia/Kuala_Lumpur', 'locale' => 'en-GB']);

    $item = Item::query()->create(['group' => 'a', 'name' => 'First']);

    expect($item->formatted_created_at)->toBe('18 Oct 2026, 22:30')
        ->and($item->formatted_updated_at)->toBe('18 Oct 2026, 22:30');
});

test('a date with a fixed offset from the database gets the named zone of the app, so date math across daylight saving stays right', function (): void {
    config(['app.timezone' => 'Europe/London']);

    $item = Item::query()->create(['group' => 'a', 'name' => 'First']);
    $item->setRawAttributes(['created_at' => '2026-07-01 12:00:00+01'] + $item->getAttributes());

    expect($item->created_at?->timezoneName)->toBe('Europe/London')
        ->and($item->created_at?->addMonths(6)->format('Y-m-d H:i'))->toBe('2027-01-01 12:00');
});

test('a date from another zone moves to the app zone before Eloquent writes it', function (): void {
    $item = Item::query()->create(['group' => 'a', 'name' => 'First']);

    $item->created_at = CarbonImmutable::parse('2026-10-26 14:30', 'Asia/Kuala_Lumpur');
    $item->save();

    expect(Item::query()->toBase()->value('created_at'))->toBe('2026-10-26 06:30:00');
});

test('a date-only value from a zone ahead of the app keeps its day', function (string $cast): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-25 23:00', 'UTC'));
    Schema::table('items', function (Blueprint $table): void {
        $table->date('due_on')->nullable();
    });

    $item = Item::query()->create(['group' => 'a', 'name' => 'First'])->mergeCasts(['due_on' => $cast]);
    $item->due_on = today('Asia/Kuala_Lumpur');
    $item->save();

    expect(Item::query()->toBase()->value('due_on'))->toStartWith('2026-10-26')
        ->and($item->fresh()?->mergeCasts(['due_on' => $cast])->due_on?->format('Y-m-d'))->toBe('2026-10-26');
})->with(['date', 'immutable_date', 'date:d/m/Y']);
