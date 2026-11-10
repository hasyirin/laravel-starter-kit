<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Enums\SignerRequirement;
use App\Domain\Shared\Letter\Support\LetterData;
use App\Domain\Shared\Organization\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Tests\Fixtures\Support\NoticeLetter;
use Tests\Fixtures\Support\PermitLabelledLetter;

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

it('refuses a value that fails its field rule', function (): void {
    $organization = Organization::factory()->create(['name' => str_repeat('a', 256)]);

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
