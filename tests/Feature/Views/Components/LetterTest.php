<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Support\CarbonCopy;
use App\Domain\Shared\Letter\Support\LetterView;
use Carbon\CarbonImmutable;

function letterView(array $overrides = []): LetterView
{
    return new LetterView(...[
        'referenceNumber' => 'REF/7',
        'date' => CarbonImmutable::parse('2026-03-10'),
        'formattedDate' => '10 March 2026',
        'signerName' => 'Aminah',
        'signerPosition' => 'Director',
        'carbonCopies' => [new CarbonCopy('Finance'), new CarbonCopy('Legal')],
        'letterhead' => null,
        'computerSigned' => false,
        ...$overrides,
    ]);
}

it('prints the reference number and the date', function (): void {
    $html = (string) $this->blade('<x-letter :letter="$letter">Body</x-letter>', ['letter' => letterView()]);

    expect($html)->toContain('REF/7')->toContain('10 March 2026')->toContain('Body');
});

it('prints no reference line without a reference number', function (): void {
    $html = (string) $this->blade('<x-letter :letter="$letter">Body</x-letter>', ['letter' => letterView(['referenceNumber' => null])]);

    expect($html)->not->toContain('REF/7')->toContain('10 March 2026');
});

it('prints the recipient, the subject and the items as text', function (): void {
    $html = (string) $this->blade(
        '<x-letter.recipient>{{ $name }}</x-letter.recipient><x-letter.subject>Subject</x-letter.subject><x-letter.item>One</x-letter.item><x-letter.item>Two</x-letter.item>',
        ['name' => '<b>Acme</b>'],
    );

    expect($html)->toContain('&lt;b&gt;Acme&lt;/b&gt;')->toContain('Subject')->toContain('One')->toContain('Two');
});

it('prints the signer name and position', function (): void {
    $html = (string) $this->blade('<x-letter.signature :letter="$letter" />', ['letter' => letterView()]);

    expect($html)->toContain('Aminah')->toContain('Director');
});

it('prints a computer signed note instead of a signing space', function (): void {
    $signed = (string) $this->blade('<x-letter.signature :letter="$letter" />', ['letter' => letterView(['computerSigned' => true])]);
    $unsigned = (string) $this->blade('<x-letter.signature :letter="$letter" />', ['letter' => letterView()]);

    expect($signed)->toContain(__('letters/letters.computer_signed'))
        ->and($unsigned)->not->toContain(__('letters/letters.computer_signed'));
});

it('prints nothing for a letter without a signer', function (): void {
    $html = (string) $this->blade('<x-letter.signature :letter="$letter" />', ['letter' => letterView(['signerName' => null, 'signerPosition' => null])]);

    expect(trim($html))->toBeEmpty();
});

it('prints the carbon copies', function (): void {
    $html = (string) $this->blade('<x-letter.carbon-copies :letter="$letter" />', ['letter' => letterView()]);

    expect($html)->toContain(__('letters/letters.carbon_copies'))->toContain('Finance')->toContain('Legal');
});

it('prints no carbon copy block without copies', function (): void {
    $html = (string) $this->blade('<x-letter.carbon-copies :letter="$letter" />', ['letter' => letterView(['carbonCopies' => []])]);

    expect(trim($html))->toBeEmpty();
});
