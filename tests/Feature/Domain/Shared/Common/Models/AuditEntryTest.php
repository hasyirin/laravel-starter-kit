<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Models\AuditEntry;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('media');
    $this->seed(PermissionSeeder::class);
});

test('activity()->log() makes an audit entry', function (): void {
    $entry = activity()->event('printed-manual-letter')->log('printed');

    expect($entry)->toBeInstanceOf(AuditEntry::class)
        ->and($entry->getMorphClass())->toBe('audit_entry');
});

test('an audit entry stores media', function (): void {
    $entry = activity()->event('printed-manual-letter')->log('printed');

    $media = $entry->addMedia(UploadedFile::fake()->createWithContent('logo.txt', 'logo'))->toMediaCollection();

    expect($entry->fresh()->getMedia())->toHaveCount(1)
        ->and($media->model_type)->toBe('audit_entry');
});

test('media of an audit entry needs system.logs.audits.view', function (bool $allowed, int $status): void {
    $entry = activity()->event('printed-manual-letter')->log('printed');
    $media = $entry->addMedia(UploadedFile::fake()->createWithContent('logo.txt', 'logo'))->toMediaCollection();
    $user = User::factory()->create();

    if ($allowed) {
        grantPermissions($user, 'system.logs.audits.view');
    }

    $this->actingAs($user)->get(route('media.show', $media))->assertStatus($status);
})->with([
    'a user with the permission' => [true, 200],
    'a user without the permission' => [false, 403],
]);
