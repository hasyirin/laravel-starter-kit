<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Letter\Support\LetterheadView;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\User\Support\Placement;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('media');
});

it('prints the images of a letterhead as data uris', function (): void {
    $letterhead = Letterhead::factory()->withImages()->create();

    $html = (string) $this->blade('<x-letterhead :letterhead="$letterhead">Body</x-letterhead>', ['letterhead' => $letterhead]);

    expect(substr_count($html, "url('data:image/png;base64,"))->toBe(2)
        ->and($html)->not->toContain('/storage/')
        ->and($html)->toContain('Body');
});

it('reserves the height of the images at full page width', function (): void {
    $letterhead = Letterhead::factory()->withImages(width: 2480, headerHeight: 400, footerHeight: 200)->create();

    $html = (string) $this->blade('<x-letterhead :letterhead="$letterhead">Body</x-letterhead>', ['letterhead' => $letterhead]);

    expect($html)->toContain('margin-top: '.(400 / 2480 * 210).'mm')
        ->and($html)->toContain('calc('.(200 / 2480 * 210).'mm + 15mm)');
});

it('finds the letterhead of a placement', function (): void {
    $letterhead = Letterhead::factory()->withImages()->create();

    $html = (string) $this->blade('<x-letterhead :placement="$placement">Body</x-letterhead>', ['placement' => new Placement($letterhead->organization_id)]);

    expect($html)->toContain("url('data:image/png;base64,");
});

it('prints a page without images when no letterhead matches', function (): void {
    $html = (string) $this->blade('<x-letterhead :placement="$placement">Body</x-letterhead>', ['placement' => new Placement(Organization::factory()->create()->id)]);

    expect($html)->not->toContain('data:image')
        ->and($html)->toContain('Body');
});

it('reserves the height of a letterhead view on page 1 and prints it once', function (): void {
    $view = new LetterheadView('tests::letters.letterhead-office', 40);

    $html = (string) $this->blade('<x-letterhead :letterhead="$view">Body</x-letterhead>', ['view' => $view]);

    expect($html)->toContain('height: 40mm')
        ->and(substr_count($html, 'Office Letterhead'))->toBe(1)
        ->and($html)->not->toContain('class="fixed inset-x-0 top-0');
});

it('prints a letterhead view that is set for every page in the fixed header', function (): void {
    $view = new LetterheadView('tests::letters.letterhead-office', 40, everyPage: true);

    $html = (string) $this->blade('<x-letterhead :letterhead="$view">Body</x-letterhead>', ['view' => $view]);

    expect($html)->toContain('class="fixed inset-x-0 top-0 overflow-hidden" style="height: 40mm"')
        ->and(substr_count($html, 'Office Letterhead'))->toBe(1);
});
