<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Actions\SaveLetterhead;
use App\Domain\Shared\Letter\Enums\LetterheadImage;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Media\Rules\Uploads;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('media');
    $this->organization = Organization::factory()->create();
});

function letterheadUpload(string $name, int $width, int $height): array
{
    return Livewire::test(FileUpload::class, ['fileRules' => ['image']])->set('uploads', UploadedFile::fake()->image($name, $width, $height))->get('items')[0];
}

test('saving keeps the placement, the name, and one header and one footer image', function (): void {
    $branch = Branch::factory()->for($this->organization)->create();
    $department = Department::factory()->for($this->organization)->create();

    $letterhead = SaveLetterhead::make()->execute(
        new Letterhead,
        $this->organization,
        $branch,
        $department,
        'Main',
        [letterheadUpload('header.png', 2480, 400)],
        [letterheadUpload('footer.png', 2480, 200)],
    );

    $letterhead = $letterhead->fresh();

    expect($letterhead->organization->is($this->organization))->toBeTrue()
        ->and($letterhead->branch->is($branch))->toBeTrue()
        ->and($letterhead->department->is($department))->toBeTrue()
        ->and($letterhead->name)->toBe('Main')
        ->and($letterhead->getMedia(LetterheadImage::Header))->toHaveCount(1)
        ->and($letterhead->getMedia(LetterheadImage::Footer))->toHaveCount(1)
        ->and($letterhead->getMedia(LetterheadImage::Header)->first()?->file_name)->toBe('header.png');
});

test('a new header replaces the old one', function (): void {
    $letterhead = Letterhead::factory()->for($this->organization)->withImages()->create();

    SaveLetterhead::make()->execute($letterhead, $this->organization, null, null, 'Main', [letterheadUpload('new.png', 2480, 100)], []);

    $letterhead = $letterhead->fresh();

    expect($letterhead->getMedia(LetterheadImage::Header))->toHaveCount(1)
        ->and($letterhead->getMedia(LetterheadImage::Header)->first()?->file_name)->toBe('new.png')
        ->and($letterhead->getMedia(LetterheadImage::Footer))->toBeEmpty();
});

test('a header that is 2479 px wide is refused', function (int $width, bool $passes): void {
    $validator = validator(['header' => [letterheadUpload('header.png', $width, 100)]], ['header' => [new Uploads(['image', 'dimensions:min_width=2480'])]]);

    expect($validator->passes())->toBe($passes);
})->with([
    'too narrow' => [2479, false],
    'wide enough' => [2480, true],
]);

test('a second letterhead for the same placement gives a validation error', function (): void {
    $branch = Branch::factory()->for($this->organization)->create();
    Letterhead::factory()->for($this->organization)->create(['branch_id' => $branch->id]);

    expect(fn (): Letterhead => SaveLetterhead::make()->execute(new Letterhead, $this->organization, $branch, null, 'Copy', [], []))
        ->toThrow(ValidationException::class);

    expect(Letterhead::query()->count())->toBe(1);
});

test('a placement without a branch or department is unique too', function (): void {
    Letterhead::factory()->for($this->organization)->create();

    expect(fn (): Letterhead => SaveLetterhead::make()->execute(new Letterhead, $this->organization, null, null, 'Copy', [], []))
        ->toThrow(ValidationException::class);
});

test('another placement, or a placement freed by a delete, is allowed', function (): void {
    $first = Letterhead::factory()->for($this->organization)->create();

    SaveLetterhead::make()->execute(new Letterhead, $this->organization, Branch::factory()->for($this->organization)->create(), null, 'Branch', [], []);
    SaveLetterhead::make()->execute(new Letterhead, Organization::factory()->create(), null, null, 'Other', [], []);
    $first->delete();
    SaveLetterhead::make()->execute(new Letterhead, $this->organization, null, null, 'Again', [], []);

    expect(Letterhead::query()->count())->toBe(3);
});

test('saving a letterhead again keeps its own placement', function (): void {
    $letterhead = Letterhead::factory()->for($this->organization)->create();

    SaveLetterhead::make()->execute($letterhead, $this->organization, null, null, 'Renamed', [], []);

    expect($letterhead->fresh()->name)->toBe('Renamed');
});
