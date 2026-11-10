<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Support\LetterTypes;
use App\Domain\Shared\Organization\Models\Organization;
use Tests\Fixtures\Models\Memo;
use Tests\Fixtures\Support\NoticeLetter;

beforeEach(function (): void {
    config()->set('system.letters.types', ['notice' => NoticeLetter::class]);
});

it('lists every configured letter type by key', function (): void {
    expect(LetterTypes::all())->toHaveKey('notice')
        ->and(LetterTypes::all()['notice'])->toBeInstanceOf(NoticeLetter::class);
});

it('finds a letter type by key', function (): void {
    expect(LetterTypes::find('notice'))->toBeInstanceOf(NoticeLetter::class);
});

it('refuses an unknown key', function (): void {
    LetterTypes::find('missing');
})->throws(InvalidArgumentException::class);

it('gives the config key of a letter type', function (): void {
    expect(LetterTypes::keyOf(new NoticeLetter))->toBe('notice')
        ->and(LetterTypes::keyOf(NoticeLetter::class))->toBe('notice');
});

it('returns only the letter types of the given source', function (): void {
    expect(array_keys(LetterTypes::forSource(new Organization)))->toBe(['notice'])
        ->and(LetterTypes::forSource(new Memo))->toBeEmpty();
});
