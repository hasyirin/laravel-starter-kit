<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Actions\ResolveLetterhead;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Letter\Support\LetterheadView;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\User\Support\Placement;
use Tests\Fixtures\Support\NoticeLetter;

beforeEach(function (): void {
    $this->signer = new Placement(Organization::factory()->create()->id);
    $this->printer = new Placement(Organization::factory()->create()->id);
    $this->signerLetterhead = Letterhead::factory()->create(['organization_id' => $this->signer->organizationId]);
    $this->printerLetterhead = Letterhead::factory()->create(['organization_id' => $this->printer->organizationId]);
});

test('the letterhead of the letter type wins', function (): void {
    $view = new LetterheadView('tests::letters.letterhead-office', 30);
    $type = new class($view) extends NoticeLetter
    {
        public function __construct(private readonly LetterheadView $view) {}

        public function letterhead(): LetterheadView
        {
            return $this->view;
        }
    };

    expect(ResolveLetterhead::make()->execute($type, $this->signer, $this->printer))->toBe($view);
});

test('the signer placement wins over the printer placement', function (): void {
    $found = ResolveLetterhead::make()->execute(new NoticeLetter, $this->signer, $this->printer);

    expect($found)->toBeInstanceOf(Letterhead::class)
        ->and($found->is($this->signerLetterhead))->toBeTrue();
});

test('the printer placement is used without a signer', function (): void {
    $found = ResolveLetterhead::make()->execute(new NoticeLetter, null, $this->printer);

    expect($found)->toBeInstanceOf(Letterhead::class)
        ->and($found->is($this->printerLetterhead))->toBeTrue();
});

test('the printer placement is used when the signer placement has no letterhead', function (): void {
    $this->signerLetterhead->delete();

    $found = ResolveLetterhead::make()->execute(new NoticeLetter, $this->signer, $this->printer);

    expect($found)->toBeInstanceOf(Letterhead::class)
        ->and($found->is($this->printerLetterhead))->toBeTrue();
});

test('no match gives null', function (): void {
    $this->signerLetterhead->delete();
    $this->printerLetterhead->delete();

    expect(ResolveLetterhead::make()->execute(new NoticeLetter, $this->signer, $this->printer))->toBeNull();
});
