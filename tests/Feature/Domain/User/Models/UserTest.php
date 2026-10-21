<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

test('deleting a user soft deletes it', function (): void {
    $user = User::factory()->create();

    $user->delete();

    expect(User::query()->find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id)?->deleted_at)->not->toBeNull();
});

test('a deleted user cannot log in', function (): void {
    $user = User::factory()->create();
    $user->delete();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->assertGuest();
});

test('two users cannot share an email', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);
    User::factory()->create(['email' => 'taken@example.com']);
})->throws(UniqueConstraintViolationException::class);

test('a deleted user does not block a new user with the same email', function (): void {
    User::factory()->create(['email' => 'taken@example.com'])->delete();
    User::factory()->create(['email' => 'taken@example.com'])->delete();

    $user = User::factory()->create(['email' => 'taken@example.com']);

    expect(User::withTrashed()->where('email', 'taken@example.com')->count())->toBe(3)
        ->and(User::query()->where('email', 'taken@example.com')->sole()->id)->toBe($user->id);
});

test('a deleted user cannot be restored while another user has the same email', function (): void {
    $deleted = User::factory()->create(['email' => 'taken@example.com']);
    $deleted->delete();
    User::factory()->create(['email' => 'taken@example.com']);

    $deleted->restore();
})->throws(UniqueConstraintViolationException::class);
