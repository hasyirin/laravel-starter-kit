<?php

declare(strict_types=1);

use App\Livewire\Components\Tab;
use Tests\Fixtures\Livewire\Tabs\OwnerPanel;

test('a tab falls back to its slug for the label', function (): void {
    expect(Tab::make('owner')->slug())->toBe('owner')
        ->and(Tab::make('owner')->resolvedLabel())->toBe('owner')
        ->and(Tab::make('owner')->label('Owner')->resolvedLabel())->toBe('Owner');
});

test('closures resolve on every read', function (): void {
    $value = 'one';
    $state = false;

    $tab = Tab::make('owner')
        ->icon(function () use (&$value): string {
            return "icon-{$value}";
        })
        ->badge(function () use (&$value): string {
            return "badge-{$value}";
        })
        ->tooltip(function () use (&$value): string {
            return "always-{$value}";
        }, function () use (&$value): string {
            return "disabled-{$value}";
        })
        ->hidden(function () use (&$state): bool {
            return $state;
        })
        ->disabled(function () use (&$state): bool {
            return $state;
        })
        ->params(function () use (&$value): array {
            return ['value' => $value];
        });

    expect($tab->resolvedIcon())->toBe('icon-one')
        ->and($tab->resolvedBadge())->toBe('badge-one')
        ->and($tab->resolvedTooltip(false))->toBe('always-one')
        ->and($tab->resolvedTooltip(true))->toBe('disabled-one')
        ->and($tab->isVisible())->toBeTrue()
        ->and($tab->isDisabled())->toBeFalse()
        ->and($tab->resolvedParams())->toBe(['value' => 'one']);

    $value = 'two';
    $state = true;

    expect($tab->resolvedIcon())->toBe('icon-two')
        ->and($tab->resolvedBadge())->toBe('badge-two')
        ->and($tab->resolvedTooltip(false))->toBe('always-two')
        ->and($tab->resolvedTooltip(true))->toBe('disabled-two')
        ->and($tab->isVisible())->toBeFalse()
        ->and($tab->isDisabled())->toBeTrue()
        ->and($tab->resolvedParams())->toBe(['value' => 'two']);
});

test('visible and hidden share one state', function (): void {
    expect(Tab::make('a')->isVisible())->toBeTrue()
        ->and(Tab::make('a')->hidden(true)->visible(true)->isVisible())->toBeTrue()
        ->and(Tab::make('a')->visible(true)->hidden(true)->isVisible())->toBeFalse()
        ->and(Tab::make('a')->hidden(fn (): bool => true)->visible(fn (): bool => true)->isVisible())->toBeTrue()
        ->and(Tab::make('a')->visible(fn (): bool => true)->hidden(fn (): bool => true)->isVisible())->toBeFalse()
        ->and(Tab::make('a')->visible(false)->isVisible())->toBeFalse();
});

test('the disabled tooltip wins only when the tab is disabled', function (): void {
    $both = Tab::make('a')->tooltip(always: 'A', disabled: 'D');
    $alwaysOnly = Tab::make('a')->tooltip(always: 'A');

    expect($both->resolvedTooltip(true))->toBe('D')
        ->and($both->resolvedTooltip(false))->toBe('A')
        ->and($alwaysOnly->resolvedTooltip(true))->toBe('A')
        ->and($alwaysOnly->resolvedTooltip(false))->toBe('A')
        ->and(Tab::make('a')->resolvedTooltip(true))->toBeNull();
});

test('a Livewire class is livewire content and a view is view content', function (): void {
    $livewire = Tab::make('a')->content(OwnerPanel::class);
    $view = Tab::make('b')->content($contents = view('welcome'));

    expect($livewire->contentKind())->toBe('livewire')
        ->and($livewire->resolvedContent())->toBe(OwnerPanel::class)
        ->and($view->contentKind())->toBe('view')
        ->and($view->resolvedContent())->toBe($contents)
        ->and(Tab::make('c')->content(fn (): string => OwnerPanel::class)->contentKind())->toBe('livewire');
});

test('a string that is not a Livewire class throws', function (): void {
    expect(fn () => Tab::make('a')->content('partials.foo')->contentKind())->toThrow(LogicException::class)
        ->and(fn () => Tab::make('a')->content(stdClass::class)->resolvedContent())->toThrow(LogicException::class)
        ->and(fn () => Tab::make('a')->contentKind())->toThrow(LogicException::class);
});

test('keep alive on view content throws', function (): void {
    $tab = Tab::make('a')->content(view('welcome'))->keepAlive();

    expect(fn () => $tab->contentKind())->toThrow(LogicException::class)
        ->and(fn () => $tab->resolvedContent())->toThrow(LogicException::class)
        ->and(Tab::make('a')->content(OwnerPanel::class)->keepAlive()->contentKind())->toBe('livewire');
});

test('keep alive and lazy default to off and turn on without an argument', function (): void {
    $tab = Tab::make('a');

    expect($tab->isKeptAlive())->toBeFalse()
        ->and($tab->isLazy())->toBeFalse()
        ->and($tab->keepAlive()->isKeptAlive())->toBeTrue()
        ->and($tab->keepAlive(false)->isKeptAlive())->toBeFalse()
        ->and($tab->lazy()->isLazy())->toBeTrue();
});

test('a tab stores the text, the badge color, and the url it is given', function (): void {
    $tab = Tab::make('a')->title('Title')->description('Description')->badgeColor('red')->url('/page?tab=a');

    expect($tab->resolvedTitle())->toBe('Title')
        ->and($tab->resolvedDescription())->toBe('Description')
        ->and($tab->resolvedBadgeColor())->toBe('red')
        ->and($tab->resolvedUrl())->toBe('/page?tab=a')
        ->and(Tab::make('b')->resolvedUrl())->toBeNull()
        ->and(Tab::make('b')->resolvedParams())->toBe([]);
});
