<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Letter\Support\LetterData;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

it('reads declared values by name', function (): void {
    $data = LetterData::from(
        [Field::text('title'), Field::date('issuedOn'), Field::number('amount'), Field::yesNo('urgent'), Field::list('items')],
        ['title' => 'Notice', 'issuedOn' => '2026-03-01', 'amount' => '12.5', 'urgent' => '1', 'items' => ['a', 'b']],
    );

    expect($data->title)->toBe('Notice')
        ->and($data->issuedOn)->toBeInstanceOf(CarbonImmutable::class)
        ->and($data->issuedOn->toDateString())->toBe('2026-03-01')
        ->and($data->amount)->toBe(12.5)
        ->and($data->urgent)->toBeTrue()
        ->and($data->items)->toBe(['a', 'b']);
});

it('returns null for an empty optional field', function (): void {
    $data = LetterData::from([Field::text('note')->optional(), Field::date('on')->optional()], []);

    expect($data->note)->toBeNull()
        ->and($data->on)->toBeNull();
});

it('keeps line breaks in lines', function (): void {
    $data = LetterData::from([Field::lines('address')], ['address' => "1 Jalan Satu\n50000 KL"]);

    expect($data->address)->toBe("1 Jalan Satu\n50000 KL");
});

it('reads an empty group as null and a filled group as letter data', function (): void {
    $fields = [Field::group('works', 'Works', [Field::text('contractor')->optional()])];

    expect(LetterData::from($fields, ['works' => null])->works)->toBeNull()
        ->and(LetterData::from($fields, [])->works)->toBeNull()
        ->and(LetterData::from($fields, ['works' => ['contractor' => 'Ali']])->works)->toBeInstanceOf(LetterData::class)
        ->and(LetterData::from($fields, ['works' => ['contractor' => 'Ali']])->works->contractor)->toBe('Ali');
});

it('reads a table as a list of letter data', function (): void {
    $data = LetterData::from(
        [Field::table('rows', [Field::text('name'), Field::number('qty')])],
        ['rows' => [['name' => 'A', 'qty' => 1], ['name' => 'B', 'qty' => 2]]],
    );

    expect($data->rows)->toHaveCount(2)
        ->and($data->rows[0])->toBeInstanceOf(LetterData::class)
        ->and($data->rows[1]->name)->toBe('B');
});

it('throws for an undeclared name', function (): void {
    $data = LetterData::from([Field::text('title')], ['title' => 'A']);

    $data->missing;
})->throws(InvalidArgumentException::class);

it('turns an image uuid into a data uri and keeps the uuid in the array', function (): void {
    $crest = Crest::query()->create(['name' => 'Crest']);
    $uuid = $crest->addMedia(base_path('tests/Fixtures/Files/pixel.png'))->preservingOriginal()->toMediaCollection()->uuid;

    $data = LetterData::from([Field::image('logo')], ['logo' => $uuid], [$crest]);

    expect($data->logo)->toStartWith('data:image/png;base64,')
        ->and($data->toArray())->toBe(['logo' => $uuid]);
});

it('turns a temporary upload into a data uri', function (): void {
    Storage::fake('local');
    UploadedFile::fake()->image('logo.png')->storeAs('livewire-tmp', 'logo.png', 'local');
    $upload = new TemporaryUploadedFile('logo.png', 'local');

    $data = LetterData::from([Field::image('logo')], ['logo' => $upload]);

    expect($data->logo)->toStartWith('data:image/png;base64,');
});

it('turns the data into an array that json can store', function (): void {
    $fields = [
        Field::date('issuedOn'),
        Field::group('works', 'Works', [Field::list('items')]),
        Field::table('rows', [Field::text('name')]),
    ];
    $values = ['issuedOn' => '2026-03-01', 'works' => ['items' => ['a']], 'rows' => [['name' => 'A']]];

    expect(LetterData::from($fields, $values)->toArray())->toBe($values);
});

it('refuses the image uuid of another model', function (): void {
    $uuid = Crest::query()->create(['name' => 'First'])->addMedia(base_path('tests/Fixtures/Files/pixel.png'))->preservingOriginal()->toMediaCollection()->uuid;
    $other = Crest::query()->create(['name' => 'Second']);

    LetterData::from([Field::image('logo')], ['logo' => $uuid], [$other]);
})->throws(InvalidArgumentException::class);

it('refuses an image uuid when there is no owner', function (): void {
    $uuid = Crest::query()->create(['name' => 'First'])->addMedia(base_path('tests/Fixtures/Files/pixel.png'))->preservingOriginal()->toMediaCollection()->uuid;

    LetterData::from([Field::image('logo')], ['logo' => $uuid]);
})->throws(InvalidArgumentException::class);

it('refuses an svg image', function (): void {
    $crest = Crest::query()->create(['name' => 'Crest']);
    $svg = tempnam(sys_get_temp_dir(), 'svg');
    file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"/>');
    $uuid = $crest->addMedia($svg)->usingFileName('logo.svg')->toMediaCollection()->uuid;

    LetterData::from([Field::image('logo')], ['logo' => $uuid], [$crest]);
})->throws(InvalidArgumentException::class);

it('refuses a temporary upload that is not a raster image', function (): void {
    Storage::fake('local');
    UploadedFile::fake()->create('note.pdf', 5, 'application/pdf')->storeAs('livewire-tmp', 'note.pdf', 'local');

    LetterData::from([Field::image('logo')], ['logo' => new TemporaryUploadedFile('note.pdf', 'local')]);
})->throws(InvalidArgumentException::class);

it('refuses to store the data of a temporary upload', function (): void {
    Storage::fake('local');
    UploadedFile::fake()->image('logo.png')->storeAs('livewire-tmp', 'logo.png', 'local');
    $data = LetterData::from([Field::image('logo')], ['logo' => new TemporaryUploadedFile('logo.png', 'local')]);

    $data->toArray();
})->throws(LogicException::class);

it('stores an empty image as null', function (): void {
    expect(LetterData::from([Field::image('logo')->optional()], ['logo' => ''])->toArray())->toBe(['logo' => null]);
});

it('stores an empty table as an empty list', function (): void {
    expect(LetterData::from([Field::table('rows', [Field::text('name')])], ['rows' => ''])->toArray())->toBe(['rows' => []]);
});

it('passes the media owners to the rows of a table and to a group', function (): void {
    $crest = Crest::query()->create(['name' => 'Crest']);
    $uuid = $crest->addMedia(base_path('tests/Fixtures/Files/pixel.png'))->preservingOriginal()->toMediaCollection()->uuid;
    $fields = [Field::table('rows', [Field::image('logo')]), Field::group('g', 'G', [Field::image('logo')])];

    $data = LetterData::from($fields, ['rows' => [['logo' => $uuid]], 'g' => ['logo' => $uuid]], [$crest]);

    expect($data->rows[0]->logo)->toStartWith('data:image/png')
        ->and($data->g->logo)->toStartWith('data:image/png')
        ->and($data->toArray())->toBe(['rows' => [['logo' => $uuid]], 'g' => ['logo' => $uuid]]);
});
