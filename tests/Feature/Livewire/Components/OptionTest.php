<?php

declare(strict_types=1);

use App\Livewire\Components\Option;
use Illuminate\Support\HtmlString;
use Tests\Fixtures\Enums\Plan;
use Tests\Fixtures\Enums\Priority;

test('an option holds a scalar value or the value of a backed enum', function (): void {
    expect(Option::make('free')->value())->toBe('free')
        ->and(Option::make(2024)->value())->toBe(2024)
        ->and(Option::make(Plan::Pro)->value())->toBe('pro')
        ->and(Option::make(Priority::High)->value())->toBe(2);
});

test('the label of an option falls back to the label of its enum, then to its value', function (): void {
    expect(Option::make(Plan::Pro)->label('Professional')->resolvedLabel())->toBe('Professional')
        ->and(Option::make(Plan::Pro)->resolvedLabel())->toBe('Pro plan')
        ->and(Option::make(Plan::Team)->resolvedLabel())->toBeInstanceOf(HtmlString::class)
        ->and(Option::make('draft')->resolvedLabel())->toBe('draft');
});

test('the icon and the color of an option fall back to its enum', function (): void {
    expect(Option::make(Plan::Free)->resolvedIcon())->toBe('gift')
        ->and(Option::make(Plan::Pro)->resolvedIcon())->toBe('star')
        ->and(Option::make(Plan::Team)->resolvedIcon())->toBeInstanceOf(HtmlString::class)
        ->and(Option::make(Plan::Free)->icon('sparkles')->resolvedIcon())->toBe('sparkles')
        ->and(Option::make(Plan::Pro)->resolvedColor())->toBe('amber')
        ->and(Option::make(Plan::Pro)->color('red')->resolvedColor())->toBe('red');
});

test('a color palette from an enum gives no color, because Flux takes color names', function (): void {
    expect(Option::make(Plan::Team)->resolvedColor())->toBeNull();
});

test('closures run each time the option renders', function (): void {
    $renders = 0;

    $option = Option::make('pro')
        ->label(function () use (&$renders): string {
            $renders++;

            return "Pro {$renders}";
        })
        ->disabled(function () use (&$renders): bool {
            return $renders > 1;
        });

    expect([$option->resolvedLabel(), $option->isDisabled()])->toBe(['Pro 1', false])
        ->and([$option->resolvedLabel(), $option->isDisabled()])->toBe(['Pro 2', true]);
});

test('the last call of hidden or visible decides whether the option shows', function (): void {
    expect(Option::make('pro')->isVisible())->toBeTrue()
        ->and(Option::make('pro')->hidden()->isVisible())->toBeFalse()
        ->and(Option::make('pro')->hidden()->visible()->isVisible())->toBeTrue()
        ->and(Option::make('pro')->visible(fn (): bool => false)->isVisible())->toBeFalse();
});

test('the text of an option is its label without HTML', function (): void {
    expect(Option::make(Plan::Team)->resolvedText())->toBe('Team plan')
        ->and(Option::make('fish')->label(new HtmlString('<b>Fish &amp; chips</b>'))->resolvedText())->toBe('Fish & chips')
        ->and(Option::make('small')->label('Size < 10')->resolvedText())->toBe('Size < 10');
});

test('collect makes an option of each label by value, enum case, and option, and leaves out hidden options', function (): void {
    $labels = fn (iterable $options): array => Option::collect($options)
        ->map(fn (Option $option): array => [$option->value(), (string) $option->resolvedLabel()])
        ->all();

    expect($labels(['MY' => 'Malaysia', 2024 => 2024, 7 => null]))->toBe([['MY', 'Malaysia'], [2024, '2024'], [7, '7']])
        ->and($labels(Plan::options()))->toBe([['free', 'Free plan'], ['pro', 'Pro plan'], ['team', '<strong>Team</strong> plan']])
        ->and($labels([Plan::Free, Option::make('legacy')->hidden(), Option::make('team')->label('Team')]))->toBe([['free', 'Free plan'], ['team', 'Team']]);
});
