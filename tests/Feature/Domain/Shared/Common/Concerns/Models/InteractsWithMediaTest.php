<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Tests\Fixtures\Enums\Folder;

test('the preview of an image is made on the media queue, not while the image is stored', function (): void {
    Storage::fake('media');
    Queue::fake([PerformConversionsJob::class]);

    $media = User::factory()->create()->addMedia(UploadedFile::fake()->image('photo.png', 600, 400))->toMediaCollection()->refresh();

    expect($media->hasGeneratedConversion('preview'))->toBeFalse();
    Queue::assertPushed(PerformConversionsJob::class, fn (PerformConversionsJob $job): bool => $job->connection === config('media-library.queue_connection_name'));
});

test('an image gets a WebP preview of at most 300 by 300 pixels, and a smaller image keeps its size', function (int $width, int $height, array $size): void {
    Storage::fake('media');

    $media = User::factory()->create()->addMedia(UploadedFile::fake()->image('photo.png', $width, $height))->toMediaCollection()->refresh();

    $preview = Storage::disk('media')->path($media->getPathRelativeToRoot('preview'));
    expect($media->hasGeneratedConversion('preview'))->toBeTrue()
        ->and(pathinfo($preview, PATHINFO_EXTENSION))->toBe('webp')
        ->and(array_slice((array) getimagesize($preview), 0, 2))->toBe($size);
})->with([
    'a large image' => [600, 400, [300, 200]],
    'a small image' => [100, 80, [100, 80]],
]);

test('the preview keeps the transparent parts of an image', function (): void {
    Storage::fake('media');
    $image = imagecreatetruecolor(60, 40);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    imagefilledrectangle($image, 20, 10, 40, 30, imagecolorallocate($image, 30, 120, 220));
    ob_start();
    imagepng($image);

    $media = User::factory()->create()->addMedia(UploadedFile::fake()->createWithContent('logo.png', (string) ob_get_clean()))->toMediaCollection()->refresh();

    $preview = imagecreatefromwebp(Storage::disk('media')->path($media->getPathRelativeToRoot('preview')));
    expect(imagecolorsforindex($preview, imagecolorat($preview, 2, 2))['alpha'])->toBe(127)
        ->and(imagecolorsforindex($preview, imagecolorat($preview, 30, 20))['alpha'])->toBe(0);
});

test('a file that is not an image and an SVG image get no preview', function (string $name, string $content): void {
    Storage::fake('media');

    $media = User::factory()->create()->addMedia(UploadedFile::fake()->createWithContent($name, $content))->toMediaCollection()->refresh();

    expect($media->hasGeneratedConversion('preview'))->toBeFalse();
})->with([
    'a text file' => ['notes.txt', 'notes'],
    'an SVG image' => ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>'],
]);

test('an enum case can name the collection when the media is read', function (): void {
    Storage::fake('media');
    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->createWithContent('notes.txt', 'notes'))->toMediaCollection(Folder::Documents->value);

    expect([$user->hasMedia(Folder::Documents), $user->hasMedia(Folder::Photos)])->toBe([true, false])
        ->and($user->getMedia(Folder::Documents)->pluck('file_name')->all())->toBe(['notes.txt']);
});
