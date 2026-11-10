<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Actions\UploadSignedScan;
use App\Domain\Shared\Letter\Enums\LetterMedia;
use App\Domain\Shared\Letter\Models\Letter;
use App\Livewire\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Fixtures\Support\NoticeLetter;

function scanItem(string $name): array
{
    return Livewire::test(FileUpload::class, ['fileRules' => ['file']])->set('uploads', UploadedFile::fake()->create($name, 10, 'application/pdf'))->get('items')[0];
}

test('a new signed scan replaces the old one', function (): void {
    Storage::fake('media');
    config(['system.letters.types' => ['notice' => NoticeLetter::class]]);
    $letter = Letter::factory()->create();

    UploadSignedScan::make()->execute($letter, [scanItem('first.pdf')]);
    UploadSignedScan::make()->execute($letter->fresh(), [scanItem('second.pdf')]);

    $media = $letter->fresh()->getMedia(LetterMedia::SignedScan);

    expect($media)->toHaveCount(1)
        ->and($media->first()?->file_name)->toBe('second.pdf');
});
