<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Actor\Models\Action;
use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\LetterData;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Letter\Support\PdfFile;
use App\Domain\Shared\Media\Enums\MediaCollection;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

class PrintLetter
{
    use Actionable;

    public function execute(Letter $letter, User $printer, bool $preview = false): PdfFile
    {
        $type = $letter->letter_type;
        $source = $letter->source;

        throw_unless($source instanceof Model, LogicException::class, "The source of letter [{$letter->id}] no longer exists.");

        $date = $letter->date ?? CarbonImmutable::today(config()->string('system.timezone'));
        $data = $type->letterData($source, $date);
        $extra = $this->extraData($letter, $type);

        if (! $preview) {
            AssignReferenceNumber::make()->execute($letter, $printer, $date);
        }

        $signerProfile = $this->signerProfile($letter);

        $view = BuildLetterView::make()->execute(
            $type,
            $letter->reference_number,
            $date,
            $signerProfile,
            $letter->carbon_copies,
            $letter->with_letterhead,
            $letter->computer_signed,
            $printer,
        );

        $content = RenderPdf::make()
            ->execute($type->view(), ['data' => $data, 'extra' => $extra, 'letter' => $view], new Preferences(config()->string('system.timezone'), $type->locale()), draft: $preview)
            ->generatePdfContent();

        $file = new PdfFile(str($letter->reference_number ?? "letter-{$letter->id}")->slug()->append('.pdf')->toString(), $content);

        if (! $preview && $type->keepsPrints()) {
            $this->keep($letter, $printer, $file);
        }

        return $file;
    }

    private function extraData(Letter $letter, LetterType $type): LetterData
    {
        try {
            return LetterData::from($type->extraFields(), $letter->extra, [$letter]);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['letter' => __('letters/letters.data_unreadable')]);
        }
    }

    private function signerProfile(Letter $letter): ?Profile
    {
        $signature = $letter->signature;

        if (! $signature instanceof Action) {
            return null;
        }

        return $signature->profile()->withTrashed()->with(['user', 'position'])->first();
    }

    private function keep(Letter $letter, User $printer, PdfFile $file): void
    {
        $letter->addMediaFromString($file->content)
            ->usingFileName($file->name)
            ->withCustomProperties(['user_id' => $printer->id, 'printed_at' => now()->toIso8601String()])
            ->toMediaCollection(MediaCollection::Prints->value);
    }
}
