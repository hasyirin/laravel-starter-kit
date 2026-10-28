<?php

declare(strict_types=1);

use App\Domain\Shared\Media\Rules\Uploads;
use App\Livewire\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Livewire;

test('stored items and uploads that pass the rules pass', function (): void {
    $upload = Livewire::test(FileUpload::class, ['fileRules' => ['file']])->set('uploads', UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'))->get('items')[0];
    $stored = ['uuid' => 'stored', 'name' => 'old.pdf', 'size' => 1, 'mime_type' => 'application/pdf'];

    $validator = validator(['documents' => [$stored, $upload]], ['documents' => [new Uploads(['mimes:pdf'])]]);

    expect($validator->passes())->toBeTrue();
});

test('each upload that fails the rules, was changed, or is gone fails with its name', function (Closure $spoil, string $message): void {
    $upload = $spoil(Livewire::test(FileUpload::class, ['fileRules' => ['file']])->set('uploads', UploadedFile::fake()->image('photo.png'))->get('items')[0]);

    $validator = validator(['documents' => [$upload]], ['documents' => [new Uploads(['mimes:pdf'])]]);

    expect($validator->errors()->get('documents'))->toBe([$message]);
})->with([
    'a file that fails the rules' => [fn (array $item): array => $item, 'photo.png was not added. The file field must be a file of type: pdf.'],
    'a changed reference' => [fn (array $item): array => [...$item, 'upload' => 'livewire-file:00000000:'.str($item['upload'])->afterLast(':')], 'photo.png is no longer on the server. Upload it again.'],
    'a deleted upload' => [function (array $item): array {
        FileUploadConfiguration::storage()->deleteDirectory(FileUploadConfiguration::path('', false));

        return $item;
    }, 'photo.png is no longer on the server. Upload it again.'],
]);
