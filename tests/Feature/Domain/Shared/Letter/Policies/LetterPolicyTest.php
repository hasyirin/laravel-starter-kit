<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Models\Permit;
use Tests\Fixtures\Policies\PermitPolicy;
use Tests\Fixtures\Support\NoticeLetter;

beforeEach(function (): void {
    Schema::create('permits', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->boolean('hidden')->default(false);
        $table->boolean('locked')->default(false);
        $table->timestampsTz();
        $table->softDeletesTz();
    });

    Relation::morphMap(['permit' => Permit::class]);
    Gate::policy(Permit::class, PermitPolicy::class);
    config(['system.letters.types' => ['notice' => NoticeLetter::class]]);

    $this->user = User::factory()->create();
});

function letterFor(Permit $permit): Letter
{
    return $permit->letters()->create(['type' => 'notice']);
}

test('each letter ability follows the source policy', function (string $ability, array $blocking): void {
    $open = Permit::query()->create(['name' => 'Open']);
    $blocked = Permit::query()->create(['name' => 'Blocked', ...$blocking]);

    expect($this->user->can($ability, letterFor($open)))->toBeTrue()
        ->and($this->user->can($ability, letterFor($blocked)))->toBeFalse();
})->with([
    'view' => ['view', ['hidden' => true]],
    'print' => ['print', ['hidden' => true]],
    'update' => ['update', ['locked' => true]],
    'delete' => ['delete', ['locked' => true]],
]);

test('create reads the source from the unsaved letter', function (): void {
    $open = Permit::query()->create(['name' => 'Open']);
    $locked = Permit::query()->create(['name' => 'Locked', 'locked' => true]);

    expect($this->user->can('create', $open->letters()->make(['type' => 'notice'])))->toBeTrue()
        ->and($this->user->can('create', $locked->letters()->make(['type' => 'notice'])))->toBeFalse();
});

test('print passes when the source allows view but not update', function (): void {
    $locked = Permit::query()->create(['name' => 'Locked', 'locked' => true]);
    $letter = letterFor($locked);

    expect($this->user->can('print', $letter))->toBeTrue()
        ->and($this->user->can('update', $letter))->toBeFalse();
});

test('the policy checks the given user, not the logged-in user', function (): void {
    $this->seed(PermissionSeeder::class);

    $admin = User::factory()->create();
    grantPermissions($admin, config()->string('system.permissions.bypass-authorization'));
    $this->actingAs($admin);

    $letter = letterFor(Permit::query()->create(['name' => 'Locked', 'locked' => true]));

    expect($this->user->can('update', $letter))->toBeFalse()
        ->and($admin->can('update', $letter))->toBeTrue();
});

test('a letter without a source passes no ability', function (string $ability): void {
    $letter = new Letter(['type' => 'notice']);

    expect($this->user->can($ability, $letter))->toBeFalse();
})->with(['view', 'print', 'create', 'update', 'delete']);

test('a letter whose source is gone passes no ability', function (): void {
    $letter = Letter::query()->forceCreate(['type' => 'notice', 'source_type' => 'permit', 'source_id' => 999]);

    expect($this->user->can('view', $letter))->toBeFalse();
});
