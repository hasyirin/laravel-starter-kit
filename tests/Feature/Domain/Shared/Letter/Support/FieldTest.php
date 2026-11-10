<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Enums\FieldType;
use App\Domain\Shared\Letter\Support\Field;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Tests\Fixtures\Models\Crest;

beforeEach(function (): void {
    Schema::create('crests', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    Relation::morphMap(['crest' => Crest::class]);
    Storage::fake('media');
});

function crestWithImage(string $name = 'Crest'): string
{
    $crest = Crest::query()->create(['name' => $name]);

    return $crest->addMedia(base_path('tests/Fixtures/Files/pixel.png'))->preservingOriginal()->toMediaCollection()->uuid;
}

/**
 * @return Field[]
 */
function groupFields(): array
{
    return [
        Field::group('minorWorks', 'Minor works', [
            Field::list('works'),
            Field::text('contractor'),
        ]),
    ];
}

it('declares a field with a name, a type and children', function (): void {
    $table = Field::table('items', [Field::text('description'), Field::number('quantity')->optional()]);

    expect($table->name)->toBe('items')
        ->and($table->type)->toBe(FieldType::Table)
        ->and($table->children)->toHaveCount(2)
        ->and($table->isOptional)->toBeFalse()
        ->and($table->children[1]->isOptional)->toBeTrue();
});

it('chains optional and label without changing the original field', function (): void {
    $field = Field::text('title');
    $changed = $field->optional()->label('Title');

    expect($field->isOptional)->toBeFalse()
        ->and($changed->isOptional)->toBeTrue()
        ->and($changed->label)->toBe('Title');
});

it('gives group, list and table rules with dot keys', function (): void {
    $rules = Field::rules(groupFields());

    expect($rules)->toHaveKeys(['minorWorks', 'minorWorks.works', 'minorWorks.works.*', 'minorWorks.contractor'])
        ->and($rules['minorWorks'])->toContain('nullable', 'array');
});

it('gives table columns a rule under each row', function (): void {
    $rules = Field::rules([Field::table('items', [Field::text('description')])]);

    expect($rules)->toHaveKeys(['items', 'items.*', 'items.*.description']);
});

it('requires the fields of a group only when the group is filled', function (): void {
    $fields = groupFields();

    expect(Validator::make(['minorWorks' => null], Field::rules($fields))->passes())->toBeTrue()
        ->and(Validator::make(['minorWorks' => ['works' => ['Paint'], 'contractor' => 'Ali']], Field::rules($fields))->passes())->toBeTrue()
        ->and(Validator::make(['minorWorks' => ['works' => ['Paint']]], Field::rules($fields))->fails())->toBeTrue();
});

it('lets an optional field stay empty', function (): void {
    $rules = Field::rules([Field::text('note')->optional(), Field::text('title')]);

    expect(Validator::make(['title' => 'A'], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['note' => 'x'], $rules)->fails())->toBeTrue();
});

it('accepts only the declared options of a choice', function (): void {
    $rules = Field::rules([Field::choice('size', ['s' => 'Small', 'l' => 'Large'])]);

    expect(Validator::make(['size' => 's'], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['size' => 'xl'], $rules)->fails())->toBeTrue();
});

it('accepts an image uuid of an owner', function (): void {
    $uuid = crestWithImage();
    $owner = Crest::query()->firstOrFail();

    expect(Validator::make(['logo' => $uuid], Field::rules([Field::image('logo')], [$owner]))->passes())->toBeTrue();
});

it('refuses an image uuid of another model', function (): void {
    $uuid = crestWithImage('First');
    $other = Crest::query()->create(['name' => 'Second']);

    expect(Validator::make(['logo' => $uuid], Field::rules([Field::image('logo')], [$other]))->fails())->toBeTrue()
        ->and(Validator::make(['logo' => $uuid], Field::rules([Field::image('logo')]))->fails())->toBeTrue();
});

it('refuses a path as an image', function (): void {
    $owner = Crest::query()->create(['name' => 'Owner']);

    expect(Validator::make(['logo' => '/etc/passwd'], Field::rules([Field::image('logo')], [$owner]))->fails())->toBeTrue();
});

it('accepts a temporary upload as an image', function (): void {
    Storage::fake('local');
    UploadedFile::fake()->image('logo.png')->storeAs('livewire-tmp', 'logo.png', 'local');
    $upload = new TemporaryUploadedFile('logo.png', 'local');

    expect(Validator::make(['logo' => $upload], Field::rules([Field::image('logo')]))->passes())->toBeTrue();
});

it('treats a group with only empty children as absent', function (): void {
    $rules = Field::rules(groupFields());

    expect(Validator::make(['minorWorks' => ['works' => [], 'contractor' => null]], $rules)->passes())->toBeTrue();
});

it('still requires the children of a filled group', function (): void {
    $rules = Field::rules(groupFields());

    expect(Validator::make(['minorWorks' => ['works' => [], 'contractor' => 'Ali']], $rules)->fails())->toBeTrue()
        ->and(Validator::make(['minorWorks' => ['works' => ['Paint'], 'contractor' => null]], $rules)->fails())->toBeTrue();
});

it('refuses a temporary upload that is not an image', function (): void {
    Storage::fake('local');
    UploadedFile::fake()->create('note.pdf', 5, 'application/pdf')->storeAs('livewire-tmp', 'note.pdf', 'local');
    $upload = new TemporaryUploadedFile('note.pdf', 'local');

    expect(Validator::make(['logo' => $upload], Field::rules([Field::image('logo')]))->fails())->toBeTrue();
});

it('refuses a malformed uuid as an image', function (): void {
    $owner = Crest::query()->create(['name' => 'Owner']);

    expect(Validator::make(['logo' => 'not-a-uuid'], Field::rules([Field::image('logo')], [$owner]))->fails())->toBeTrue();
});

it('refuses an svg as an image', function (): void {
    $crest = Crest::query()->create(['name' => 'Crest']);
    $svg = tempnam(sys_get_temp_dir(), 'svg');
    file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"/>');
    $uuid = $crest->addMedia($svg)->usingFileName('logo.svg')->toMediaCollection()->uuid;

    expect(Validator::make(['logo' => $uuid], Field::rules([Field::image('logo')], [$crest]))->fails())->toBeTrue();
});

it('refuses an invalid table row', function (): void {
    $rules = Field::rules([Field::table('rows', [Field::text('name'), Field::number('qty')])]);

    expect(Validator::make(['rows' => [['name' => 'A', 'qty' => 1]]], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['rows' => [['name' => 'A', 'qty' => 'many']]], $rules)->fails())->toBeTrue()
        ->and(Validator::make(['rows' => [['qty' => 1]]], $rules)->fails())->toBeTrue()
        ->and(Validator::make(['rows' => ['text']], $rules)->fails())->toBeTrue();
});

it('refuses a wrong number, date or yes-no value', function (): void {
    $rules = Field::rules([Field::number('amount'), Field::date('on'), Field::yesNo('urgent')]);
    $valid = ['amount' => 5, 'on' => '2026-03-01', 'urgent' => true];

    expect(Validator::make($valid, $rules)->passes())->toBeTrue()
        ->and(Validator::make([...$valid, 'amount' => 'abc'], $rules)->fails())->toBeTrue()
        ->and(Validator::make([...$valid, 'on' => 'not a date'], $rules)->fails())->toBeTrue()
        ->and(Validator::make([...$valid, 'urgent' => 'maybe'], $rules)->fails())->toBeTrue();
});
