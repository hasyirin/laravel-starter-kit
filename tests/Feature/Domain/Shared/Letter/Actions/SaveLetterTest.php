<?php

declare(strict_types=1);

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Letter\Actions\AssignReferenceNumber;
use App\Domain\Shared\Letter\Actions\SaveLetter;
use App\Domain\Shared\Letter\Enums\LetterMedia;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\CarbonCopy;
use App\Domain\Shared\Letter\Support\LetterTypes;
use App\Domain\Shared\Letter\Support\PrintDetails;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\Components\FileUpload;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Tests\Fixtures\Support\ExtraNoticeLetter;
use Tests\Fixtures\Support\NoticeLetter;
use Tests\Fixtures\Support\NumberedNoticeLetter;
use Tests\Fixtures\Support\OverrideNoticeLetter;
use Tests\Fixtures\Support\RestrictedNoticeLetter;
use Tests\Fixtures\Support\SignedNoticeLetter;
use Tests\Fixtures\Support\SilentNoticeLetter;

beforeEach(function (): void {
    Storage::fake('media');
    config(['system.letters.types' => [
        'notice' => NoticeLetter::class,
        'signed' => SignedNoticeLetter::class,
        'silent' => SilentNoticeLetter::class,
        'restricted' => RestrictedNoticeLetter::class,
        'extra' => ExtraNoticeLetter::class,
        'numbered' => NumberedNoticeLetter::class,
        'override' => OverrideNoticeLetter::class,
    ]]);
    $this->organization = Organization::factory()->create();
    $this->printer = User::factory()->create();
    Profile::factory()->for($this->organization)->for($this->printer)->create();
});

function signerWithPosition(?string $position = 'Clerk'): User
{
    $user = User::factory()->create();

    if ($position === null) {
        Profile::factory()->for($user)->create();

        return $user;
    }

    $position = Position::factory()->create(['name' => $position]);
    Profile::factory()->for($position->organization)->for($position)->for($user)->create();

    return $user;
}

function details(?User $signer = null, bool $withLetterhead = false, bool $computerSigned = false): PrintDetails
{
    return new PrintDetails('REF/1', null, CarbonImmutable::parse('2026-03-04'), $signer, [new CarbonCopy('Finance')], $withLetterhead, $computerSigned);
}

function numberDetails(?string $referenceNumber = null, ?int $sequenceNumber = null, ?User $signer = null): PrintDetails
{
    return new PrintDetails($referenceNumber, $sequenceNumber, null, $signer, [], false, false);
}

function saveLetter(string $type, PrintDetails $details, array $extra = [], ?Letter $letter = null): Letter
{
    return SaveLetter::make()->execute($letter ?? new Letter, LetterTypes::find($type), test()->organization, test()->printer, $details, $extra);
}

function extraUpload(): TemporaryUploadedFile
{
    $item = Livewire::test(FileUpload::class, ['fileRules' => ['image']])->set('uploads', UploadedFile::fake()->image('stamp.png', 100, 100))->get('items')[0];

    return TemporaryUploadedFile::unserializeFromLivewireRequest($item['upload']);
}

test('saving keeps the source, the type, the reference number, the date, and the carbon copies', function (): void {
    $letter = saveLetter('notice', details())->fresh();

    expect($letter->source->is($this->organization))->toBeTrue()
        ->and($letter->type)->toBe('notice')
        ->and($letter->reference_number)->toBe('REF/1')
        ->and($letter->date?->toDateString())->toBe('2026-03-04')
        ->and($letter->carbon_copies)->toEqual([new CarbonCopy('Finance')]);
});

test('a signer makes one sign action', function (): void {
    $signer = signerWithPosition();

    $letter = saveLetter('notice', details($signer));

    expect($letter->actions()->ofName(Act::Sign)->count())->toBe(1)
        ->and($letter->signature?->actor?->is($signer))->toBeTrue();
});

test('a new signer replaces the actor of the one sign action', function (): void {
    $letter = saveLetter('notice', details(signerWithPosition()));
    $other = signerWithPosition('Manager');

    saveLetter('notice', details($other), letter: $letter);

    expect($letter->actions()->ofName(Act::Sign)->count())->toBe(1)
        ->and($letter->fresh()->signature?->actor?->is($other))->toBeTrue();
});

test('no signer deletes the sign action', function (): void {
    $letter = saveLetter('notice', details(signerWithPosition()));

    saveLetter('notice', details(), letter: $letter);

    expect($letter->actions()->ofName(Act::Sign)->count())->toBe(0);
});

test('a signer without a position is refused', function (): void {
    expect(fn (): Letter => saveLetter('notice', details(signerWithPosition(null))))
        ->toThrow(ValidationException::class);
    expect(Letter::query()->count())->toBe(0);
});

test('a letter type that requires a signer refuses no signer', function (): void {
    expect(fn (): Letter => saveLetter('signed', details()))->toThrow(ValidationException::class);
    expect(Letter::query()->count())->toBe(0);
});

test('a letter type that hides the signer refuses a signer', function (): void {
    expect(fn (): Letter => saveLetter('silent', details(signerWithPosition())))->toThrow(LogicException::class);
});

test('computer signed forces the letterhead', function (): void {
    $letter = saveLetter('notice', details(withLetterhead: false, computerSigned: true));

    expect($letter->fresh()->with_letterhead)->toBeTrue()
        ->and($letter->fresh()->computer_signed)->toBeTrue();
});

test('a letter type that is not allowed for the user is refused', function (): void {
    expect(fn (): Letter => saveLetter('restricted', details()))->toThrow(LogicException::class);
    expect(Letter::query()->count())->toBe(0);
});

test('bad extra values are refused and nothing is saved', function (): void {
    expect(fn (): Letter => saveLetter('extra', details(), ['remark' => ['not text']]))->toThrow(ValidationException::class);
    expect(Letter::query()->count())->toBe(0);
});

test('extra values are kept', function (): void {
    $letter = saveLetter('extra', details(), ['remark' => 'Urgent']);

    expect($letter->fresh()->extra)->toBe(['remark' => 'Urgent', 'stamp' => null]);
});

test('an extra image is stored in the data collection and its uuid is kept', function (): void {
    $letter = saveLetter('extra', details(), ['remark' => 'Urgent', 'stamp' => extraUpload()])->fresh();

    $media = $letter->getMedia(LetterMedia::Data);

    expect($media)->toHaveCount(1)
        ->and($letter->extra['stamp'])->toBe($media->first()?->uuid);
});

test('an extra image that is kept by uuid is not stored twice', function (): void {
    $letter = saveLetter('extra', details(), ['remark' => 'Urgent', 'stamp' => extraUpload()])->fresh();
    $uuid = $letter->extra['stamp'];

    saveLetter('extra', details(), ['remark' => 'Changed', 'stamp' => $uuid], $letter);

    expect($letter->fresh()->getMedia(LetterMedia::Data))->toHaveCount(1)
        ->and($letter->fresh()->extra['stamp'])->toBe($uuid);
});

test('a typed reference number keeps the sequence key of the printer organization', function (): void {
    expect(saveLetter('notice', numberDetails('REF/1'))->fresh()?->sequence_key)->toBe("notice:{$this->organization->id}");
});

test('a typed duplicate reference number is refused', function (): void {
    saveLetter('notice', numberDetails('REF/1'));

    expect(fn (): Letter => saveLetter('notice', numberDetails('REF/1')))->toThrow(ValidationException::class);
    expect(Letter::query()->count())->toBe(1);
});

test('a typed reference number of a deleted letter is refused', function (): void {
    saveLetter('notice', numberDetails('REF/1'))->delete();

    expect(fn (): Letter => saveLetter('notice', numberDetails('REF/1')))->toThrow(ValidationException::class);
});

test('the same typed reference number in another sequence passes', function (): void {
    saveLetter('notice', numberDetails('REF/1'));
    $signer = signerWithPosition();

    $letter = saveLetter('notice', numberDetails('REF/1', signer: $signer));

    expect($letter->fresh()?->sequence_key)->toBe("notice:{$signer->profile?->organization_id}")
        ->and($letter->fresh()?->reference_number)->toBe('REF/1');
});

test('a letter keeps its own typed reference number when saved again', function (): void {
    $letter = saveLetter('notice', numberDetails('REF/1'));

    expect(saveLetter('notice', numberDetails('REF/1'), letter: $letter)->reference_number)->toBe('REF/1');
});

test('the sequence key does not change after the letter has a reference number', function (): void {
    $letter = saveLetter('notice', numberDetails('REF/1'));
    $signer = signerWithPosition();

    saveLetter('notice', numberDetails('REF/2', signer: $signer), letter: $letter);

    expect($letter->fresh()?->sequence_key)->toBe("notice:{$this->organization->id}")
        ->and($letter->fresh()?->reference_number)->toBe('REF/2');
});

test('a unique index error becomes a validation error', function (): void {
    saveLetter('notice', numberDetails('REF/1'));
    $letter = saveLetter('notice', numberDetails('REF/2'));
    Letter::saving(function (Letter $saving): void {
        if ($saving->reference_number === 'REF/3') {
            Letter::query()->whereKeyNot($saving->id)->update(['reference_number' => 'REF/3']);
        }
    });

    expect(fn (): Letter => saveLetter('notice', numberDetails('REF/3'), letter: $letter))->toThrow(ValidationException::class);
});

test('automatic numbering ignores a typed reference number', function (): void {
    expect(saveLetter('numbered', numberDetails('REF/1'))->fresh()?->reference_number)->toBeNull();
});

test('an override number is formatted by the letter type', function (): void {
    $letter = saveLetter('override', numberDetails(sequenceNumber: 7))->fresh();

    expect($letter?->reference_number)->toBe('N/7')
        ->and($letter?->sequence_number)->toBe(7)
        ->and($letter?->sequence_cycle)->toBeNull();
});

test('an override number above the counter is skipped by the counter', function (): void {
    saveLetter('override', numberDetails(sequenceNumber: 2));

    $first = AssignReferenceNumber::make()->execute(saveLetter('override', numberDetails()), $this->printer);
    $second = AssignReferenceNumber::make()->execute(saveLetter('override', numberDetails()), $this->printer);

    expect([$first->reference_number, $second->reference_number])->toBe(['N/1', 'N/3']);
});

test('an override number at or below the counter is accepted when no letter has it', function (): void {
    AssignReferenceNumber::make()->execute(saveLetter('override', numberDetails()), $this->printer);
    AssignReferenceNumber::make()->execute(saveLetter('override', numberDetails()), $this->printer)->forceDelete();

    expect(saveLetter('override', numberDetails(sequenceNumber: 2))->fresh()?->reference_number)->toBe('N/2');
});

test('an override number that a letter has is refused', function (): void {
    AssignReferenceNumber::make()->execute(saveLetter('override', numberDetails()), $this->printer);

    expect(fn (): Letter => saveLetter('override', numberDetails(sequenceNumber: 1)))->toThrow(ValidationException::class);
});
