<?php

declare(strict_types=1);

use App\Livewire\Components\Option;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\HtmlString;
use Tests\Fixtures\Enums\Plan;

test('an autocomplete binds its input to its property, names it by its label, and suggests its options', function (): void {
    $view = $this->withViewErrors(['state' => 'Choose a state in Malaysia.'])->blade('<x-autocomplete label="State" wire:model="state" :options="[\'Johor\', \'Kedah\']" required />');

    $document = HTMLDocument::createFromString((string) $view, LIBXML_NOERROR);
    $input = $document->querySelector('ui-select[autocomplete] input[data-input-control]');

    expect($input?->getAttribute('wire:model'))->toBe('state')
        ->and($input?->getAttribute('aria-label'))->toBe('State')
        ->and($input?->hasAttribute('required'))->toBeTrue()
        ->and(array_map(fn (Element $option): string => trim($option->textContent), iterator_to_array($document->querySelectorAll('ui-option'))))->toBe(['Johor', 'Kedah']);
    $view->assertSee('Choose a state in Malaysia.');
});

test('an option shows the label of an option object or an enum case as text', function (): void {
    $document = HTMLDocument::createFromString((string) $this->blade('<x-autocomplete label="Plan" wire:model="plan" :options="$options" />', ['options' => [
        Option::make('perak')->label(new HtmlString('<b>Perak</b>'))->disabled(),
        Plan::Team,
    ]]), LIBXML_NOERROR);

    $options = iterator_to_array($document->querySelectorAll('ui-option'));

    expect(array_map(fn (Element $option): string => trim($option->textContent), $options))->toBe(['Perak', 'Team plan'])
        ->and($options[0]->hasAttribute('disabled'))->toBeTrue()
        ->and($document->querySelector('ui-option b'))->toBeNull();
});

test('the input is named by its own aria-label, or by the text of an HTML label', function (string $template, string $name): void {
    $input = HTMLDocument::createFromString((string) $this->blade($template, ['label' => new HtmlString('<b>R&amp;D</b> team')]), LIBXML_NOERROR)->querySelector('input[data-input-control]');

    expect($input?->getAttribute('aria-label'))->toBe($name);
})->with([
    'its own aria-label' => ['<x-autocomplete wire:model="medium" aria-label="Medium" />', 'Medium'],
    'an HTML label' => ['<x-autocomplete :label="$label" wire:model="team" />', 'R&D team'],
]);
