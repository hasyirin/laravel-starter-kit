<?php

declare(strict_types=1);

use App\Domain\Shared\Media\Actions\SyncMedia;
use App\Domain\User\Models\User;
use App\Livewire\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\Fixtures\Enums\Folder;

test('the collection gets the uploads, keeps the listed media, loses the rest, and takes the order of the items', function (): void {
    Storage::fake('media');
    $user = User::factory()->create();
    $kept = $user->addMedia(UploadedFile::fake()->createWithContent('kept.txt', 'kept'))->toMediaCollection('documents');
    $dropped = $user->addMedia(UploadedFile::fake()->createWithContent('dropped.txt', 'dropped'))->toMediaCollection('documents');
    $upload = Livewire::test(FileUpload::class, ['fileRules' => ['file']])->set('uploads', UploadedFile::fake()->createWithContent('new.txt', 'new'))->get('items')[0];
    $stored = collect(FileUpload::items($user, 'documents'))->firstWhere('uuid', $kept->uuid);

    SyncMedia::make()->execute($user, [$upload, $stored], Folder::Documents);

    expect($user->getMedia('documents')->map(fn (Media $media): array => [$media->file_name, $media->order_column])->all())->toBe([['new.txt', 1], ['kept.txt', 2]])
        ->and($user->getFirstMedia('documents')?->name)->toBe('new');
    $this->assertModelMissing($dropped);
    FileUploadConfiguration::storage()->assertMissing(FileUploadConfiguration::path(str($upload['upload'])->afterLast(':')->value(), false));
});

test('media of another model or another collection stays where it is, also when the items name it', function (): void {
    Storage::fake('media');
    $user = User::factory()->create();
    $foreign = User::factory()->create()->addMedia(UploadedFile::fake()->createWithContent('foreign.txt', 'foreign'))->toMediaCollection('documents');
    $avatar = $user->addMedia(UploadedFile::fake()->createWithContent('avatar.txt', 'avatar'))->toMediaCollection('avatar');
    $items = array_map(fn (Media $media): array => ['uuid' => (string) $media->uuid, 'name' => $media->file_name, 'size' => $media->size, 'mime_type' => (string) $media->mime_type], [$foreign, $avatar]);

    SyncMedia::make()->execute($user, $items, 'documents');

    expect($user->getMedia('documents'))->toBeEmpty()
        ->and($foreign->fresh()?->model_id)->not->toBe($user->id)
        ->and($avatar->fresh()?->collection_name)->toBe('avatar');
});

test('an upload that was changed or is gone stops the sync before anything changes', function (Closure $spoil): void {
    Storage::fake('media');
    $user = User::factory()->create();
    $kept = $user->addMedia(UploadedFile::fake()->createWithContent('kept.txt', 'kept'))->toMediaCollection('documents');
    $upload = $spoil(Livewire::test(FileUpload::class, ['fileRules' => ['file']])->set('uploads', UploadedFile::fake()->createWithContent('new.txt', 'new'))->get('items')[0]);

    expect(fn () => SyncMedia::make()->execute($user, [$upload], 'documents'))->toThrow(RuntimeException::class, 'The upload of [new.txt] is missing.');

    expect($user->fresh()?->getMedia('documents')->pluck('id')->all())->toBe([$kept->id]);
})->with([
    'a changed reference' => [fn (array $item): array => [...$item, 'upload' => str($item['upload'])->replaceMatches('/^livewire-file:\w+:/', 'livewire-file:00000000:')->value()]],
    'a deleted upload' => [function (array $item): array {
        FileUploadConfiguration::storage()->deleteDirectory(FileUploadConfiguration::path('', false));

        return $item;
    }],
]);
