<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a media url goes through the media route, also the url of a conversion', function (): void {
    Storage::fake('media');
    $media = User::factory()->create()->addMedia(UploadedFile::fake()->createWithContent('notes.txt', 'notes'))->toMediaCollection();

    expect($media->getUrl())->toBe(url("media/{$media->uuid}"))
        ->and($media->getUrl('preview'))->toBe(url("media/{$media->uuid}/preview"));
});
