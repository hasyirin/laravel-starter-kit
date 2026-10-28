<?php

declare(strict_types=1);

namespace App\Domain\Shared\Media\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Uploads implements ValidationRule
{
    /**
     * @param  list<string>|string  $rules
     */
    public function __construct(private readonly array|string $rules) {}

    /**
     * @param  list<string>|string  $rules
     */
    public static function rejection(TemporaryUploadedFile $file, array|string $rules): ?string
    {
        $validator = validator(['file' => $file], ['file' => $rules]);

        return $validator->fails()
            ? (string) __('common/uploads.rejected', ['name' => $file->getClientOriginalName(), 'reason' => $validator->errors()->first('file')])
            : null;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach (is_array($value) ? $value : [] as $item) {
            if (! is_array($item) || ! isset($item['upload'])) {
                continue;
            }

            $file = is_string($item['upload']) ? TemporaryUploadedFile::unserializeFromLivewireRequest($item['upload']) : null;

            if (! $file instanceof TemporaryUploadedFile || ! $file->exists()) {
                $fail((string) __('common/uploads.expired', ['name' => is_string($item['name'] ?? null) ? $item['name'] : '']));

                continue;
            }

            if (($rejection = self::rejection($file, $this->rules)) !== null) {
                $fail($rejection);
            }
        }
    }
}
