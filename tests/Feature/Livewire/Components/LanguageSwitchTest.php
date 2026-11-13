<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Livewire\Components\LanguageSwitch;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

test('the switch keeps the chosen language of a guest in a cookie and loads the page again', function (): void {
    $component = Livewire::withQueryParams(['email' => 'user@example.com'])->test(LanguageSwitch::class);

    expect($component->get('url'))->toBe(request()->url().'?email=user%40example.com');

    $component->call('switch', 'ms')->assertRedirect($component->get('url'));

    expect(cookie()->queued('language')?->getValue())->toBe('ms');
});

test('the switch saves the chosen language of a signed-in user, with an audit entry', function (): void {
    $user = User::factory()->unverified()->create(['language' => 'en']);

    $this->actingAs($user);

    Livewire::test(LanguageSwitch::class)->call('switch', 'ms');

    expect($user->refresh()->language)->toBe('ms')
        ->and(cookie()->queued('language')?->getValue())->toBe('ms')
        ->and(Activity::query()->where('event', 'updated')->whereMorphedTo('subject', $user)->exists())->toBeTrue();
});

test('the switch refuses a language that the app does not list', function (): void {
    $user = User::factory()->create(['language' => 'en']);

    $this->actingAs($user);

    Livewire::test(LanguageSwitch::class)->call('switch', 'fr')->assertNotFound();

    expect($user->refresh()->language)->toBe('en')
        ->and(cookie()->hasQueued('language'))->toBeFalse();
});

test('the switch names each language in its own language and marks the current one', function (): void {
    app()->setLocale('ms');

    Livewire::test(LanguageSwitch::class)
        ->assertSeeInOrder(['English', 'aria-current="true"', 'Bahasa Melayu'], false);
});

test('the switch shows nothing when the app lists one language', function (): void {
    config(['system.languages' => ['en' => 'English']]);

    Livewire::test(LanguageSwitch::class)->assertDontSee('English');
});

test('the auth pages show the switch to guests and to signed-in users', function (): void {
    $this->get(route('login'))->assertOk()->assertSeeLivewire(LanguageSwitch::class);

    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSeeLivewire(LanguageSwitch::class);
});

test('the short switch shows an abbreviation for each language, with the name for screen readers', function (): void {
    Livewire::test(LanguageSwitch::class, ['short' => true])->assertSeeInOrder(['aria-label="English"', 'EN', '|', 'aria-label="Bahasa Melayu"', 'BM'], false)->assertDontSeeHtml('Bahasa Melayu</button>');
});

test('a language without an abbreviation shows its code in capitals', function (): void {
    config(['system.languages' => ['en' => 'English', 'ta' => 'Tamil'], 'system.language_abbreviations' => []]);

    Livewire::test(LanguageSwitch::class, ['short' => true])->assertSeeInOrder(['EN', 'TA']);
});

test('the signed-in pages show the short switch', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeLivewire(LanguageSwitch::class)
        ->assertSeeInOrder(['EN', 'BM']);
});
