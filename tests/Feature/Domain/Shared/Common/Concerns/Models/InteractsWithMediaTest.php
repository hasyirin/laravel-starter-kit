<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Livewire\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\Fixtures\Enums\Folder;

function notesUpload(): TemporaryUploadedFile
{
    $item = Livewire::test(FileUpload::class, ['fileRules' => ['file']])->set('uploads', UploadedFile::fake()->createWithContent('notes.txt', 'notes'))->get('items')[0];

    return TemporaryUploadedFile::unserializeFromLivewireRequest($item['upload']);
}

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
    $media = $user->addMedia(UploadedFile::fake()->createWithContent('notes.txt', 'notes'))->toMediaCollection(Folder::Documents->value);

    expect([$user->hasMedia(Folder::Documents), $user->hasMedia(Folder::Photos)])->toBe([true, false])
        ->and($user->getMedia(Folder::Documents)->pluck('file_name')->all())->toBe(['notes.txt'])
        ->and($user->getFirstMedia(Folder::Documents)?->file_name)->toBe('notes.txt')
        ->and($user->getFirstMediaUrl(Folder::Documents))->toBe(route('media.show', $media));
});

test('an enum case can name the collection that is cleared', function (): void {
    Storage::fake('media');
    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->createWithContent('notes.txt', 'notes'))->toMediaCollection(Folder::Documents->value);
    $user->addMedia(UploadedFile::fake()->createWithContent('photo.txt', 'photo'))->toMediaCollection(Folder::Photos->value);

    $user->clearMediaCollection(Folder::Documents);

    expect([$user->hasMedia(Folder::Documents), $user->hasMedia(Folder::Photos)])->toBe([false, true]);
});

test('an upload becomes media with the file name of the client and a name without the extension', function (): void {
    Storage::fake('media');
    $user = User::factory()->create();
    $upload = notesUpload();

    $media = $user->addMediaFromUpload($upload, Folder::Documents);

    expect([$media->file_name, $media->name, $media->collection_name])->toBe(['notes.txt', 'notes', 'documents'])
        ->and(Storage::disk('media')->get($media->getPathRelativeToRoot()))->toBe('notes')
        ->and($upload->exists())->toBeTrue();
});

test('an upload that Media Library refuses leaves no temp file', function (): void {
    Storage::fake('media');
    config(['media-library.max_file_size' => 1]);
    $upload = notesUpload();

    $user = User::factory()->create();

    expect(fn (): Media => $user->addMediaFromUpload($upload, 'documents'))->toThrow(function (FileIsTooBig $exception): void {
        $path = str($exception->getMessage())->between('`', '`')->value();

        expect($path)->toStartWith(sys_get_temp_dir())
            ->and(file_exists($path))->toBeFalse();
    });
});

test('an upload cannot join a model that is not saved', function (): void {
    $upload = notesUpload();

    expect(fn (): Media => User::factory()->make()->addMediaFromUpload($upload))->toThrow(LogicException::class, 'Save the model before you add an upload to its media.');
});

test('an upload whose file cannot be read stores no media', function (): void {
    Storage::fake('media');
    $user = User::factory()->create();
    $upload = notesUpload();
    $upload->delete();

    expect(fn (): Media => $user->addMediaFromUpload($upload, Folder::Documents))->toThrow(RuntimeException::class, 'The upload cannot be read, so it cannot become media.')
        ->and(Media::query()->count())->toBe(0);
});
