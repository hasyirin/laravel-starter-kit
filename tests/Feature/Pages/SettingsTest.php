<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Models\User;
use App\Livewire\Settings\DeleteUserForm;
use App\Livewire\Settings\Preferences;
use App\Livewire\Settings\Security;
use App\Livewire\Settings\Tabs;
use App\Livewire\Settings\UserInformation;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Lazy;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('settings'));

    $response->assertRedirect(route('login'));
});

test('the settings page opens on user information and shows the four tabs in order', function (): void {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('settings'));

    $response->assertOk()
        ->assertSeeLivewire(UserInformation::class)
        ->assertDontSeeLivewire(Preferences::class)
        ->assertSeeInOrder([
            'data-tab="user-information"', 'User information',
            'data-tab="preferences"', 'Preferences',
            'data-tab="security"', 'Security',
            'data-tab="account"', 'Account',
        ], false)
        ->assertDontSee('data-tab="appearance"', false);
});

test('each tab opens from the url', function (string $tab, string $component): void {
    $this->actingAs(User::factory()->create())->withSession(['auth.password_confirmed_at' => time()]);

    $response = $this->get(route('settings', ['tab' => $tab]));

    $response->assertOk()
        ->assertSeeLivewire($component)
        ->assertDontSeeLivewire(UserInformation::class);
})->with([
    'preferences' => ['preferences', Preferences::class],
    'security' => ['security', Security::class],
    'account' => ['account', DeleteUserForm::class],
]);

test('each old settings url redirects to its tab', function (string $route, string $target): void {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route($route));

    $response->assertRedirect($target);
})->with([
    'user information' => ['user-information.edit', '/settings'],
    'preferences' => ['preferences.edit', '/settings?tab=preferences'],
    'security' => ['security.edit', '/settings?tab=security'],
    'appearance' => ['appearance.edit', '/settings?tab=preferences'],
]);

test('the settings page has its title in the language of the user', function (): void {
    $this->actingAs(User::factory()->create(['language' => 'ms']));

    $response = $this->get(route('settings'));

    $response->assertOk()->assertSee('<title>Tetapan - ', false);
});

test('user information and preferences open for a user with an unverified email or phone number', function (string $state, string $tab): void {
    $this->actingAs(User::factory()->{$state}()->create());

    $response = $this->get(route('settings', ['tab' => $tab]));

    $response->assertOk();
})->with([
    'an unverified email' => 'unverified',
    'an unverified phone number' => 'unverifiedPhone',
])->with([
    'user information' => 'user-information',
    'preferences' => 'preferences',
]);

test('the preferences tab shows the theme control to a user with an unverified email or phone number', function (string $state): void {
    $this->actingAs(User::factory()->{$state}()->create());

    $response = $this->get(route('settings', ['tab' => 'preferences']));

    $response->assertOk()->assertSee('x-model="$flux.appearance"', false);
})->with([
    'an unverified email' => 'unverified',
    'an unverified phone number' => 'unverifiedPhone',
]);

test('the security tab sends a user with an unverified email to verify it', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);
    // Each test of a protected tab turns off passkeys and two factor, so no nested component with its own check renders.
    $this->disableFeature(Feature::Passkeys, Feature::TwoFactorAuthentication);
    $this->actingAs(User::factory()->unverified()->create())->withSession(['auth.password_confirmed_at' => time()]);

    $response = $this->get(route('settings', ['tab' => 'security']));

    $response->assertRedirect(route('verification.notice'));
});

test('the security tab sends a user with an unverified phone number to verify it', function (): void {
    $this->skipUnlessFeature(Feature::PhoneVerification);
    $this->disableFeature(Feature::Passkeys, Feature::TwoFactorAuthentication);
    $this->actingAs(User::factory()->unverifiedPhone()->create())->withSession(['auth.password_confirmed_at' => time()]);

    $response = $this->get(route('settings', ['tab' => 'security']));

    $response->assertRedirect(route('phone-verification.notice'));
});

test('the security tab asks for the password and then leads back to the security tab', function (): void {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('settings', ['tab' => 'security']));

    $response->assertRedirect(route('password.confirm'))
        ->assertSessionHas('url.intended', route('settings', ['tab' => 'security']));
});

test('a click on the security tab sends a user with an unverified email to verify it', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);
    $this->disableFeature(Feature::Passkeys, Feature::TwoFactorAuthentication);
    $this->actingAs(User::factory()->unverified()->create())->withSession(['auth.password_confirmed_at' => time()]);
    $page = $this->get(route('settings'));

    $response = callLivewireAction($page, 'settings.tabs', 'switchTab', ['security']);

    $response->assertRedirect(route('verification.notice'));
});

test('a click on the security tab sends a user with an unverified phone number to verify it', function (): void {
    $this->skipUnlessFeature(Feature::PhoneVerification);
    $this->disableFeature(Feature::Passkeys, Feature::TwoFactorAuthentication);
    $this->actingAs(User::factory()->unverifiedPhone()->create())->withSession(['auth.password_confirmed_at' => time()]);
    $page = $this->get(route('settings'));

    $response = callLivewireAction($page, 'settings.tabs', 'switchTab', ['security']);

    $response->assertRedirect(route('phone-verification.notice'));
});

test('a click on the security tab asks for the password and then leads back to the security tab', function (): void {
    $this->actingAs(User::factory()->create());
    $page = $this->get(route('settings'));

    $response = callLivewireAction($page, 'settings.tabs', 'switchTab', ['security']);

    $response->assertRedirect(route('password.confirm'))
        ->assertSessionHas('url.intended', route('settings', ['tab' => 'security']));
});

test('an open security tab sends the user to verify the email once it is unverified', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);
    $this->disableFeature(Feature::Passkeys, Feature::TwoFactorAuthentication);
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $page = $this->get(route('settings', ['tab' => 'security']));
    $user->forceFill(['email_verified_at' => null])->save();

    $response = callLivewireAction($page, 'settings.security', 'closeModal');

    $response->assertRedirect(route('verification.notice'));
});

test('the security tab mounts with the page and never lazily, so its password step runs on mount', function (): void {
    $security = collect(new Tabs()->tabs())->first(fn ($tab): bool => $tab->slug() === 'security');
    $attributes = new ReflectionClass(Security::class)->getAttributes();

    expect($security->isLazy())->toBeFalse()
        ->and(collect($attributes)->map(fn (ReflectionAttribute $attribute): string => $attribute->getName())->all())
        ->not->toContain(Lazy::class)
        ->not->toContain(Defer::class);
});

test('a settings form with a password field posts, so a submit without JavaScript puts no password into the url', function (string $tab, string $action): void {
    $this->actingAs(User::factory()->create())->withSession(['auth.password_confirmed_at' => time()]);

    $response = $this->get(route('settings', ['tab' => $tab]));

    preg_match('/<form[^>]*wire:submit="'.$action.'"[^>]*>/', (string) $response->getContent(), $form);
    expect($form[0] ?? '')->toContain('method="POST"');
})->with([
    'update password' => ['security', 'updatePassword'],
    'delete account' => ['account', 'delete'],
]);
