<?php

declare(strict_types=1);

use App\Domain\Shared\Media\Observers\MediaObserver;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\Fixtures\Models\SoftDeletingMedia;

test('a rollback removes the files of a media that the transaction added', function (): void {
    Storage::fake('media');
    $user = User::factory()->create();

    expect(fn (): mixed => DB::transaction(function () use ($user): void {
        $user->addMedia(UploadedFile::fake()->createWithContent('new.txt', 'new'))->toMediaCollection('documents');

        throw new RuntimeException('A later step failed.');
    }))->toThrow(RuntimeException::class);

    expect(Storage::disk('media')->allFiles())->toBeEmpty();
    $this->assertDatabaseEmpty('media');
});

test('a rollback keeps a deleted media and its files', function (): void {
    Storage::fake('media');
    $media = User::factory()->create()->addMedia(UploadedFile::fake()->createWithContent('kept.txt', 'kept'))->toMediaCollection('documents');

    expect(fn (): mixed => DB::transaction(function () use ($media): void {
        $media->delete();

        throw new RuntimeException('A later step failed.');
    }))->toThrow(RuntimeException::class);

    $this->assertModelExists($media);
    Storage::disk('media')->assertExists("{$media->id}/kept.txt");
});

test('the files of a deleted media stay until the transaction commits', function (): void {
    Storage::fake('media');
    $media = User::factory()->create()->addMedia(UploadedFile::fake()->createWithContent('old.txt', 'old'))->toMediaCollection('documents');

    DB::transaction(function () use ($media): void {
        $media->delete();

        Storage::disk('media')->assertExists("{$media->id}/old.txt");
    });

    $this->assertModelMissing($media);
    Storage::disk('media')->assertMissing("{$media->id}/old.txt");
});

test('a delete with no open transaction removes the files at once', function (): void {
    Storage::fake('media');
    $media = User::factory()->create()->addMedia(UploadedFile::fake()->createWithContent('old.txt', 'old'))->toMediaCollection('documents');

    $media->delete();

    $this->assertModelMissing($media);
    Storage::disk('media')->assertMissing("{$media->id}/old.txt");
});

test('a savepoint that rolls back keeps the files of a media that it deleted, while the outer transaction commits', function (): void {
    Storage::fake('media');
    $media = User::factory()->create()->addMedia(UploadedFile::fake()->createWithContent('kept.txt', 'kept'))->toMediaCollection('documents');

    DB::transaction(function () use ($media): void {
        expect(fn (): mixed => DB::transaction(function () use ($media): void {
            $media->delete();

            throw new RuntimeException('A later step failed.');
        }))->toThrow(RuntimeException::class);
    });

    $this->assertModelExists($media);
    Storage::disk('media')->assertExists("{$media->id}/kept.txt");
});

test('a savepoint that rolls back removes the files of a media that it added, while the outer transaction commits', function (): void {
    Storage::fake('media');
    $user = User::factory()->create();

    DB::transaction(function () use ($user): void {
        expect(fn (): mixed => DB::transaction(function () use ($user): void {
            $user->addMedia(UploadedFile::fake()->createWithContent('new.txt', 'new'))->toMediaCollection('documents');

            throw new RuntimeException('A later step failed.');
        }))->toThrow(RuntimeException::class);
    });

    expect(Storage::disk('media')->allFiles())->toBeEmpty();
    $this->assertDatabaseEmpty('media');
});

test('a file removal that fails after the commit is reported, and the delete does not throw', function (): void {
    Storage::fake('media');
    Exceptions::fake();
    $media = User::factory()->create()->addMedia(UploadedFile::fake()->createWithContent('old.txt', 'old'))->toMediaCollection('documents');
    app()->instance(Filesystem::class, new class(resolve(Factory::class)) extends Filesystem
    {
        public function removeAllFiles(Media $media): void
        {
            throw new RuntimeException('The file removal failed.');
        }
    });

    DB::transaction(fn (): ?bool => $media->delete());

    $this->assertModelMissing($media);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'The file removal failed.');
});

test('a file removal that fails on a rollback is reported, and the first exception still goes out', function (): void {
    Storage::fake('media');
    Exceptions::fake();
    $user = User::factory()->create();
    app()->instance(Filesystem::class, new class(resolve(Factory::class)) extends Filesystem
    {
        public function removeAllFiles(Media $media): void
        {
            throw new RuntimeException('The file removal failed.');
        }
    });

    expect(fn (): mixed => DB::transaction(function () use ($user): void {
        $user->addMedia(UploadedFile::fake()->createWithContent('new.txt', 'new'))->toMediaCollection('documents');

        throw new RuntimeException('A later step failed.');
    }))->toThrow(RuntimeException::class, 'A later step failed.');

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'The file removal failed.');
});

describe('with a soft-deleting media model', function (): void {
    beforeEach(function (): void {
        Schema::table('media', function (Blueprint $table): void {
            $table->softDeletes();
        });
        Relation::morphMap(['soft_deleting_media' => SoftDeletingMedia::class]);
        config(['media-library.media_model' => SoftDeletingMedia::class]);
        SoftDeletingMedia::observe(MediaObserver::class);
    });

    test('a force delete inside a transaction removes the files after the commit', function (): void {
        Storage::fake('media');
        $media = User::factory()->create()->addMedia(UploadedFile::fake()->createWithContent('old.txt', 'old'))->toMediaCollection('documents');

        DB::transaction(function () use ($media): void {
            $media->forceDelete();

            Storage::disk('media')->assertExists("{$media->id}/old.txt");
        });

        $this->assertModelMissing($media);
        Storage::disk('media')->assertMissing("{$media->id}/old.txt");
    });

    test('a soft delete keeps the files', function (): void {
        Storage::fake('media');
        $media = User::factory()->create()->addMedia(UploadedFile::fake()->createWithContent('kept.txt', 'kept'))->toMediaCollection('documents');

        $media->delete();

        $this->assertSoftDeleted($media);
        Storage::disk('media')->assertExists("{$media->id}/kept.txt");
    });
});
