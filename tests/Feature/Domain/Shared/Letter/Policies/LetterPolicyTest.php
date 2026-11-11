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
use Tests\Fixtures\Support\PermitLetter;
use Tests\Fixtures\Support\PermitLockedLetter;
use Tests\Fixtures\Support\PermitRestrictedLetter;
use Tests\Fixtures\Support\PermitSignedLetter;

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

test('create without a type passes when the user may create one letter type of the source', function (): void {
    config(['system.letters.types' => ['permit' => PermitLetter::class]]);

    $open = Permit::query()->create(['name' => 'Open']);
    $locked = Permit::query()->create(['name' => 'Locked', 'locked' => true]);

    expect($this->user->can('create', $open->letters()->make()))->toBeTrue()
        ->and($this->user->can('create', $locked->letters()->make()))->toBeFalse();
});

test('create without a type fails when the source has no letter type', function (): void {
    config(['system.letters.types' => []]);

    expect($this->user->can('create', Permit::query()->create(['name' => 'Open'])->letters()->make()))->toBeFalse();
});

test('a letter type that is not allowed for the user passes only view and delete', function (string $ability, bool $allowed): void {
    config(['system.letters.types' => ['restricted' => PermitRestrictedLetter::class]]);
    $letter = Permit::query()->create(['name' => 'Open'])->letters()->create(['type' => 'restricted']);

    expect($this->user->can($ability, $letter))->toBe($allowed);
})->with([
    'view' => ['view', true],
    'print' => ['print', false],
    'create' => ['create', false],
    'update' => ['update', false],
    'delete' => ['delete', true],
]);

test('create without a type skips letter types that are not allowed for the user', function (): void {
    config(['system.letters.types' => ['restricted' => PermitRestrictedLetter::class]]);

    expect($this->user->can('create', Permit::query()->create(['name' => 'Open'])->letters()->make()))->toBeFalse();
});

test('create fails for a letter type the user cannot save, with or without the type', function (string $class, array $permit): void {
    config(['system.letters.types' => ['blocked' => $class]]);
    $source = Permit::query()->create(['name' => 'Open', ...$permit]);

    expect($this->user->can('create', $source->letters()->make()))->toBeFalse()
        ->and($this->user->can('create', $source->letters()->make(['type' => 'blocked'])))->toBeFalse();
})->with([
    'a required signer without print' => [PermitSignedLetter::class, ['hidden' => true]],
    'a locked required extra field' => [PermitLockedLetter::class, []],
]);

test('a letter of an unknown type passes only view, and delete with the update ability of the source', function (string $ability, array $permit, bool $allowed): void {
    $letter = Permit::query()->create(['name' => 'Open', ...$permit])->letters()->create(['type' => 'removed']);

    expect($this->user->can($ability, $letter))->toBe($allowed);
})->with([
    'view' => ['view', [], true],
    'print' => ['print', [], false],
    'create' => ['create', [], false],
    'update' => ['update', [], false],
    'delete' => ['delete', [], true],
    'delete on a locked source' => ['delete', ['locked' => true], false],
]);
