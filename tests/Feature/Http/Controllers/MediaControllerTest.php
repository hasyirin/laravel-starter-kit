<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('media');

    $this->seed(PermissionSeeder::class);
});

test('a guest is sent to the login page', function (): void {
    $media = User::factory()->create()->addMedia(UploadedFile::fake()->createWithContent('notes.txt', 'notes'))->toMediaCollection();

    $response = $this->get(route('media.show', $media));

    $response->assertRedirect(route('login'));
});

test('a user who may not view the owner of the media is refused, also for a conversion that does not exist', function (?string $conversion): void {
    $media = User::factory()->create()->addMedia(UploadedFile::fake()->createWithContent('notes.txt', 'notes'))->toMediaCollection();

    $response = $this->actingAs(User::factory()->create())->get(route('media.show', array_filter(['media' => $media, 'conversion' => $conversion])));

    $response->assertForbidden();
})->with([
    'the file' => [null],
    'a conversion' => ['preview'],
]);

test('a user who may view the owner gets the file, inline only for an image that is not SVG', function (string $name, string $content, string $disposition): void {
    $owner = User::factory()->create();
    $media = $owner->addMedia(UploadedFile::fake()->createWithContent($name, $content))->toMediaCollection();

    $response = $this->actingAs($owner)->get(route('media.show', $media));

    $response->assertOk()
        ->assertHeader('Content-Disposition', "{$disposition}; filename=\"{$name}\"")
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertStreamedContent($content);
})->with([
    'an image' => ['photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='), 'inline'],
    'an SVG image' => ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'attachment'],
    'another file' => ['notes.txt', 'notes', 'attachment'],
]);

test('a conversion is sent inline with the type of its own file', function (): void {
    $owner = User::factory()->create();
    $media = $owner->addMedia(UploadedFile::fake()->image('scan.png', 600, 400))->toMediaCollection();

    $response = $this->actingAs($owner)->get(route('media.show', [$media, 'preview']));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/webp')
        ->assertHeader('Content-Disposition', 'inline; filename=scan-preview.webp')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('a conversion that was never made is not found', function (): void {
    $owner = User::factory()->create();
    $media = $owner->addMedia(UploadedFile::fake()->createWithContent('notes.txt', 'notes'))->toMediaCollection();

    $response = $this->actingAs($owner)->get(route('media.show', [$media, 'preview']));

    $response->assertNotFound();
});

test('media whose owner is gone is not found', function (): void {
    $owner = User::factory()->create();
    $media = $owner->addMedia(UploadedFile::fake()->createWithContent('notes.txt', 'notes'))->toMediaCollection();
    User::query()->whereKey($owner->id)->toBase()->delete();

    $response = $this->actingAs(User::factory()->create())->get(route('media.show', $media));

    $response->assertNotFound();
});
