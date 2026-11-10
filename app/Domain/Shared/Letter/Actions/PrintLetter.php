<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Actor\Models\Action;
use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Letter\Enums\LetterMedia;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Letter\Support\LetterData;
use App\Domain\Shared\Letter\Support\LetterheadView;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Letter\Support\LetterView;
use App\Domain\Shared\Letter\Support\PdfFile;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Placement;
use App\Domain\User\Support\Preferences;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use IntlDateFormatter;
use InvalidArgumentException;
use LogicException;

class PrintLetter
{
    use Actionable;

    public function execute(Letter $letter, User $printer, bool $preview = false): PdfFile
    {
        if (! $preview) {
            AssignReferenceNumber::make()->execute($letter, $printer);
        }

        $type = $letter->letter_type;
        $source = $letter->source;

        throw_unless($source instanceof Model, LogicException::class, "The source of letter [{$letter->id}] no longer exists.");

        $date = $letter->date ?? CarbonImmutable::today(config()->string('system.timezone'));
        $signerProfile = $this->signerProfile($letter);

        try {
            $data = $type->letterData($source, $date);
            $extra = LetterData::from($type->extraFields(), $letter->extra, [$letter]);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['letter' => __('letters/letters.data_unreadable')]);
        }

        $view = new LetterView(
            $letter->reference_number,
            $date,
            $this->formatDate($type, $date),
            $signerProfile?->name,
            $signerProfile?->position?->name,
            $letter->carbon_copies,
            $letter->with_letterhead ? $this->letterhead($type, $signerProfile, $printer) : null,
            $letter->computer_signed,
            $preview,
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

    private function signerProfile(Letter $letter): ?Profile
    {
        $signature = $letter->signature;

        if (! $signature instanceof Action) {
            return null;
        }

        return $signature->profile()->withTrashed()->with(['user', 'position'])->first();
    }

    private function letterhead(LetterType $type, ?Profile $signerProfile, User $printer): Letterhead|LetterheadView|null
    {
        return ResolveLetterhead::make()->execute(
            $type,
            $signerProfile instanceof Profile ? Placement::of($signerProfile) : null,
            Placement::of($printer->profile),
        );
    }

    private function formatDate(LetterType $type, CarbonImmutable $date): string
    {
        return (string) new IntlDateFormatter(
            $type->locale(),
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            new DateTimeZone($date->format('P')),
            IntlDateFormatter::GREGORIAN,
            $type->dateFormat(),
        )->format($date);
    }

    private function keep(Letter $letter, User $printer, PdfFile $file): void
    {
        $letter->addMediaFromString($file->content)
            ->usingFileName($file->name)
            ->withCustomProperties(['user_id' => $printer->id, 'printed_at' => now()->toIso8601String()])
            ->toMediaCollection(LetterMedia::Prints->value);
    }
}
