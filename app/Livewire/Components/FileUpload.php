<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Domain\Shared\Common\Contracts\Models\HasMedia;
use App\Domain\Shared\Media\Rules\Uploads;
use App\Livewire\Component;
use BackedEnum;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Reactive;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FileUpload extends Component
{
    use WithFileUploads;

    /** @var list<array{uuid: string, name: string, size: int, mime_type: string, preview?: bool, upload?: ?string}> */
    #[Modelable]
    public array $items = [] {
        // The bound property can be an unset key of an array, such as form.documents in an empty form.
        set(?array $items) => $items ?? [];
    }

    /** @var TemporaryUploadedFile|list<TemporaryUploadedFile>|null */
    public TemporaryUploadedFile|array|null $uploads = null;

    /** @var list<string> */
    #[Locked]
    public array $fileRules = [];

    #[Locked]
    public bool $multiple = false;

    #[Locked]
    public ?int $max = null;

    #[Locked]
    public ?string $accept = null;

    #[Locked]
    public ?string $heading = null;

    #[Locked]
    public ?string $text = null;

    #[Locked]
    public bool $inline = false;

    #[Reactive]
    public bool $disabled = false;

    /**
     * @return list<array{uuid: string, name: string, size: int, mime_type: string, preview: bool}>
     */
    public static function items(HasMedia $model, BackedEnum|string $collection = 'default'): array
    {
        return array_values($model->getMedia($collection)->map(fn (Media $media): array => [
            'uuid' => $media->uuid,
            'name' => $media->file_name,
            'size' => $media->size,
            'mime_type' => $media->mime_type,
            'preview' => $media->hasGeneratedConversion('preview'),
        ])->all());
    }

    public function mount(): void
    {
        throw_if($this->fileRules === [], InvalidArgumentException::class, "A file upload needs rules, for example ['mimes:pdf', 'max:10240'].");
    }

    public function updatedUploads(): void
    {
        $files = array_filter(is_array($this->uploads) ? $this->uploads : [$this->uploads], fn (mixed $file): bool => $file instanceof TemporaryUploadedFile);
        $rejections = [];

        $this->uploads = null;
        $this->resetErrorBag('uploads');

        if ($this->disabled) {
            return;
        }

        foreach ($files as $file) {
            $rejection = $this->isFull()
                ? trans_choice('common/uploads.too_many', $this->limit(), ['name' => $file->getClientOriginalName(), 'max' => $this->limit()])
                : Uploads::rejection($file, $this->fileRules);

            if ($rejection !== null) {
                $file->delete();
                $rejections[] = $rejection;

                continue;
            }

            $this->items = [...$this->items, [
                'uuid' => Str::uuid7()->toString(),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'upload' => 'livewire-file:'.TemporaryUploadedFile::signPath($file->getFilename()),
            ]];
        }

        if ($rejections !== []) {
            $this->addError('uploads', implode(' ', $rejections));
        }
    }

    public function remove(string $uuid): void
    {
        if ($this->disabled) {
            return;
        }

        $this->items = array_values(array_filter($this->items, function (array $item) use ($uuid): bool {
            if ($item['uuid'] !== $uuid) {
                return true;
            }

            $this->pendingFile($item)?->delete();

            return false;
        }));

        $this->resetErrorBag('uploads');
    }

    public function sort(string $uuid, int $position): void
    {
        $index = array_search($uuid, array_column($this->items, 'uuid'), true);

        if ($this->disabled || ! is_int($index)) {
            return;
        }

        $items = $this->items;
        $moved = array_splice($items, $index, 1);

        array_splice($items, max(0, min($position, count($items))), 0, $moved);

        $this->items = $items;
    }

    public function render(): View
    {
        return view('livewire.components.file-upload', [
            'full' => $this->isFull(),
            'acceptedTypes' => $this->acceptedTypes(),
            'limits' => $this->limits(),
            'previews' => collect($this->items)->mapWithKeys(fn (array $item): array => [$item['uuid'] => $this->preview($item)])->all(),
        ]);
    }

    private function limit(): int
    {
        return $this->multiple ? ($this->max ?? PHP_INT_MAX) : 1;
    }

    private function isFull(): bool
    {
        return count($this->items) >= $this->limit();
    }

    private function acceptedTypes(): ?string
    {
        if ($this->accept !== null) {
            return $this->accept;
        }

        $types = collect($this->fileRules)->flatMap(fn (string $rule): array => match (true) {
            str_starts_with($rule, 'mimes:'), str_starts_with($rule, 'extensions:') => str($rule)->after(':')->explode(',')->map(fn (string $extension): string => '.'.trim($extension))->all(),
            str_starts_with($rule, 'mimetypes:') => str($rule)->after(':')->explode(',')->map(fn (string $type): string => trim($type))->all(),
            $rule === 'image' => ['image/*'],
            default => [],
        });

        return $types->isEmpty() ? null : $types->join(',');
    }

    private function limits(): ?string
    {
        if ($this->text !== null) {
            return $this->text;
        }

        $types = collect($this->fileRules)
            ->filter(fn (string $rule): bool => str_starts_with($rule, 'mimes:') || str_starts_with($rule, 'extensions:'))
            ->flatMap(fn (string $rule): array => str($rule)->after(':')->upper()->explode(',')->map(fn (string $extension): string => trim($extension))->all())
            ->join(', ');

        $maximum = collect($this->fileRules)->first(fn (string $rule): bool => str_starts_with($rule, 'max:'));
        $size = $maximum === null ? '' : Number::fileSize((int) str($maximum)->after(':')->value() * 1024);

        return match (true) {
            $types !== '' && $size !== '' => (string) __('common/uploads.text', ['types' => $types, 'size' => $size]),
            $size !== '' => (string) __('common/uploads.size', ['size' => $size]),
            $types !== '' => $types,
            default => null,
        };
    }

    /**
     * @param  array{uuid: string, name: string, size: int, mime_type: string, preview?: bool, upload?: ?string}  $item
     */
    private function preview(array $item): ?string
    {
        if (! isset($item['upload'])) {
            return match (true) {
                $item['preview'] ?? false => route('media.show', [$item['uuid'], 'preview']),
                str_starts_with($item['mime_type'], 'image/') && $item['mime_type'] !== 'image/svg+xml' => route('media.show', $item['uuid']),
                default => null,
            };
        }

        $file = $this->pendingFile($item);
        $url = $file?->isPreviewable() ? $file->temporaryUrl() : null;

        return is_string($url) ? $url : null;
    }

    /**
     * @param  array{uuid: string, name: string, size: int, mime_type: string, preview?: bool, upload?: ?string}  $item
     */
    private function pendingFile(array $item): ?TemporaryUploadedFile
    {
        $file = isset($item['upload']) ? TemporaryUploadedFile::unserializeFromLivewireRequest($item['upload']) : null;

        return $file instanceof TemporaryUploadedFile && $file->exists() ? $file : null;
    }
}
