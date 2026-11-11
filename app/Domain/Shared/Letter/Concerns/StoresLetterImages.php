<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Concerns;

use App\Domain\Shared\Common\Contracts\Models\HasMedia;
use App\Domain\Shared\Letter\Enums\FieldType;
use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Media\Enums\MediaCollection;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait StoresLetterImages
{
    /** @var list<Media> */
    private array $storedImages = [];

    /**
     * @param  list<Field>  $fields
     * @param  array<array-key, mixed>  $values
     * @return array<string, mixed>
     */
    protected function storeImages(HasMedia $owner, MediaCollection $collection, array $fields, array $values, ?Model $copiedOwner = null): array
    {
        return collect($fields)
            ->mapWithKeys(fn (Field $field): array => [$field->name => $this->storeValue($owner, $collection, $field, $values[$field->name] ?? null, $copiedOwner)])
            ->all();
    }

    private function storeValue(HasMedia $owner, MediaCollection $collection, Field $field, mixed $value, ?Model $copiedOwner): mixed
    {
        return match (true) {
            $field->type === FieldType::Image && $value instanceof TemporaryUploadedFile => $this->storeUpload($owner, $collection, $value),
            $field->type === FieldType::Image && is_string($value) && $copiedOwner instanceof Model => $this->storeCopy($owner, $collection, $value, $copiedOwner),
            $field->type === FieldType::Group && is_array($value) => $this->storeImages($owner, $collection, $field->children, $value, $copiedOwner),
            default => $value,
        };
    }

    private function storeCopy(HasMedia $owner, MediaCollection $collection, string $uuid, Model $copiedOwner): string
    {
        $media = Field::ownedImage($uuid, [$copiedOwner]);

        if (! $media instanceof Media) {
            return $uuid;
        }

        $copy = $media->copy($owner, $collection->value);
        $this->storedImages[] = $copy;

        return $copy->uuid;
    }

    private function storeUpload(HasMedia $owner, MediaCollection $collection, TemporaryUploadedFile $file): string
    {
        $media = $owner->addMediaFromStream($file->readStream())
            ->usingFileName($file->getClientOriginalName())
            ->toMediaCollection($collection->value);

        $this->storedImages[] = $media;

        return $media->uuid;
    }

    protected function removeStoredImages(): void
    {
        foreach ($this->storedImages as $media) {
            rescue(fn () => resolve(Filesystem::class)->removeAllFiles($media));
        }

        $this->storedImages = [];
    }
}
