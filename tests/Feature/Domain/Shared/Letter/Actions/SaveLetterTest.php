<?php

declare(strict_types=1);

use App\Domain\Shared\Actor\Enums\Act;
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
    ]]);
    $this->organization = Organization::factory()->create();
    $this->printer = User::factory()->create();
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
