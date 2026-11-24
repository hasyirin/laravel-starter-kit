<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Enums\SignerRequirement;
use App\Domain\Shared\Letter\Support\LetterData;
use App\Domain\Shared\Organization\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Tests\Fixtures\Support\LabelledFieldsNoticeLetter;
use Tests\Fixtures\Support\NoticeLetter;
use Tests\Fixtures\Support\PermitLabelledLetter;
use Tests\Fixtures\Support\RemarkNoticeLetter;

beforeEach(function (): void {
    config()->set('system.letters.types', ['notice' => NoticeLetter::class]);
    $this->date = CarbonImmutable::parse('2026-03-01');
});

it('turns the mapping into validated letter data', function (): void {
    $organization = Organization::factory()->create(['name' => 'Acme']);

    $data = (new NoticeLetter)->letterData($organization, $this->date);

    expect($data)->toBeInstanceOf(LetterData::class)
        ->and($data->organizationName)->toBe('Acme')
        ->and($data->works)->toBeNull()
        ->and($data->issuedOn->toDateString())->toBe('2026-03-01');
});

it('refuses undeclared letter data', function (): void {
    new NoticeLetter(extraKey: 'x')->letterData(Organization::factory()->create(), $this->date);
})->throws(LogicException::class, 'notice: undeclared letter data [x]');

it('refuses missing letter data', function (): void {
    new NoticeLetter(drop: 'organizationName')->letterData(Organization::factory()->create(), $this->date);
})->throws(LogicException::class, 'notice: missing letter data [organizationName]');

it('reads an optional field that the mapping leaves out as null', function (): void {
    config()->set('system.letters.types', ['remark' => RemarkNoticeLetter::class]);

    expect((new RemarkNoticeLetter)->letterData(Organization::factory()->create(), $this->date)->remark)->toBeNull();
});

it('refuses a value that fails its field rule', function (): void {
    $organization = Organization::factory()->create();
    $organization->name = str_repeat('a', 256);

    (new NoticeLetter)->letterData($organization, $this->date);
})->throws(ValidationException::class);

it('maps letter abilities to source abilities', function (): void {
    $type = new NoticeLetter;

    expect($type->sourceAbility('view'))->toBe('view')
        ->and($type->sourceAbility('print'))->toBe('view')
        ->and($type->sourceAbility('create'))->toBe('update')
        ->and($type->sourceAbility('update'))->toBe('update')
        ->and($type->sourceAbility('delete'))->toBe('update');
});

it('has safe defaults', function (): void {
    $type = new NoticeLetter;

    expect($type->dateFormat())->toBe('d MMMM y')
        ->and($type->locale())->toBe(config('system.locale'))
        ->and($type->signer())->toBe(SignerRequirement::Optional)
        ->and($type->keepsPrints())->toBeFalse()
        ->and($type->offersLetterhead())->toBeTrue()
        ->and($type->offersComputerSigned())->toBeFalse()
        ->and($type->lockedFields())->toBeEmpty()
        ->and($type->extraFields())->toBeEmpty();
});

test('the label is the headline of the config key by default', function (): void {
    config()->set('system.letters.types', ['late_notice' => NoticeLetter::class]);

    expect(new NoticeLetter()->label())->toBe('Late Notice');
});

test('a letter type can override its label', function (): void {
    config()->set('system.letters.types', ['labelled' => PermitLabelledLetter::class]);

    expect(new PermitLabelledLetter()->label())->toBe('Permit Approval');
});

function labelledLetterDataErrors(Organization $organization, CarbonImmutable $date): array
{
    try {
        (new LabelledFieldsNoticeLetter)->letterData($organization, $date);
    } catch (ValidationException $validationException) {
        return $validationException->errors();
    }

    throw new RuntimeException('The data was not refused.');
}

it('names a group member by its label when a value fails its rule', function (): void {
    config()->set('system.letters.types', ['labelled' => LabelledFieldsNoticeLetter::class]);
    $organization = Organization::factory()->create();
    $organization->name = str_repeat('a', 256);

    expect(labelledLetterDataErrors($organization, $this->date)['works.contractor'])->toBe(['The contractor name field must not be greater than 255 characters.']);

    app()->setLocale('ms');

    expect(labelledLetterDataErrors($organization, $this->date)['works.contractor'])->toBe(['Medan contractor name tidak boleh melebihi 255 aksara.']);
});
