<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Models\User;
use Pest\Browser\Api\PendingAwaitablePage;

function visitSecurityTabWithFreshPassword(): PendingAwaitablePage
{
    $page = visit(route('settings', ['tab' => 'security'], absolute: false))
        ->withUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36');

    $page->type('[name="password"]', 'password')
        ->click('[data-test="confirm-password-button"]');

    expect($page->script(browserScript("return await until(() => location.search === '?tab=security' && document.querySelector('[data-test=\"update-password-button\"]'));")))->toBeTrue();

    return $page;
}

function passkeyModalScript(string $body): string
{
    $helpers = <<<'SCRIPT'
        const modal = () => document.querySelector('[x-data="passkeyModal"]');
        const dialog = () => document.querySelector('dialog[wire\\:close="close"]');
        const nameField = () => modal()?.querySelector('input');
        const errorText = () => modal()?.querySelector('[x-text="error"]');
        const opened = () => dialog()?.open === true && nameField()?.checkVisibility() === true;
        const plusButton = () => [...document.querySelectorAll('button')].find((button) => button.innerText.trim() === 'Passkey');
        const stubPasskeys = async () => {
            await until(() => window.Passkeys);
            window.Passkeys = {
                isSupported: () => true,
                register: async () => {
                    if (window.passkeyError) {
                        const error = new Error(window.passkeyError);
                        error.name = 'NotAllowedError';

                        throw error;
                    }

                    return {};
                },
            };
        };
        const typeName = (value) => {
            nameField().value = value;
            nameField().dispatchEvent(new Event('input', { bubbles: true }));
        };
        SCRIPT;

    return browserScript("{$helpers}\n{$body}");
}

test('a click on the Security tab asks for the password, and the password opens the Security tab', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit(route('settings', absolute: false));

    $page->click('[data-tab="security"]');

    expect($page->script(browserScript("return await until(() => location.pathname === '/user/confirm-password');")))->toBeTrue();

    $page->type('[name="password"]', 'password')
        ->click('[data-test="confirm-password-button"]');

    expect($page->script(browserScript("return await until(() => location.pathname === '/settings' && location.search === '?tab=security');")))->toBeTrue();

    $page->assertSee(__('user/settings.password.heading'))
        ->assertNoJavaScriptErrors();
});

test('a click on + Passkey opens the dialog with the focus in the name field and a device name in it', function (): void {
    $this->skipUnlessFeature(Feature::Passkeys);
    $this->actingAs(User::factory()->create());
    $page = visitSecurityTabWithFreshPassword();

    $result = $page->script(passkeyModalScript(<<<'SCRIPT'
        await stubPasskeys();
        plusButton().click();

        if (! await until(opened)) {
            return 'the dialog did not open';
        }

        await until(() => document.activeElement === nameField());

        return {
            heading: dialog().querySelector('[data-flux-heading]')?.innerText.trim(),
            focused: document.activeElement === nameField(),
            name: nameField().value,
        };
        SCRIPT));

    expect($result)->toBe([
        'heading' => 'Add Passkey',
        'focused' => true,
        'name' => 'Chrome on Mac',
    ]);

    $page->assertNoJavaScriptErrors();
});

test('a failed registration shows its error in red', function (): void {
    $this->skipUnlessFeature(Feature::Passkeys);
    $this->actingAs(User::factory()->create());
    $page = visitSecurityTabWithFreshPassword();

    $result = $page->script(passkeyModalScript(<<<'SCRIPT'
        await stubPasskeys();
        window.passkeyError = 'The device refused the passkey.';
        plusButton().click();
        await until(opened);

        modal().querySelector('[type="submit"]').click();

        if (! await until(() => errorText()?.checkVisibility() && errorText().innerText.trim() !== '')) {
            return 'no error text';
        }

        return {
            text: errorText().innerText.trim(),
            red: errorText().classList.contains('text-red-600'),
        };
        SCRIPT));

    expect($result['text'])->toBe('The device refused the passkey.')
        ->and($result['red'])->toBeTrue();

    $page->assertNoJavaScriptErrors();
});

test('a closed dialog opens again with no error and with the default name', function (string $close): void {
    $this->skipUnlessFeature(Feature::Passkeys);
    $this->actingAs(User::factory()->create());
    $page = visitSecurityTabWithFreshPassword();

    expect($page->script(passkeyModalScript(<<<'SCRIPT'
        await stubPasskeys();
        window.passkeyError = 'The device refused the passkey.';
        plusButton().click();
        await until(opened);
        modal().querySelector('[type="submit"]').click();
        await until(() => errorText()?.checkVisibility());
        typeName('Old laptop');

        return nameField().value === 'Old laptop' && errorText().checkVisibility();
        SCRIPT)))->toBeTrue();

    if ($close === 'escape') {
        $page->keys('[x-data="passkeyModal"] input', 'Escape');
    } else {
        $page->script(passkeyModalScript(match ($close) {
            'cancel' => "[...dialog().querySelectorAll('button')].find((button) => button.innerText.trim() === 'Cancel').click();",
            'the x' => "dialog().querySelector('button[aria-label=\"Close modal\"]').click();",
            'a backdrop click' => <<<'SCRIPT'
                const outside = { bubbles: true, clientX: 2, clientY: 2 };
                dialog().dispatchEvent(new MouseEvent('mousedown', outside));
                dialog().dispatchEvent(new MouseEvent('click', outside));
                SCRIPT,
        }));
    }

    $result = $page->script(passkeyModalScript(<<<'SCRIPT'
        if (! await until(() => dialog()?.open !== true && ! modal())) {
            return 'the dialog did not close';
        }

        window.passkeyError = null;
        plusButton().click();

        if (! await until(opened)) {
            return 'the dialog did not open again';
        }

        return {
            name: nameField().value,
            error: errorText().checkVisibility(),
        };
        SCRIPT));

    expect($result)->toBe(['name' => 'Chrome on Mac', 'error' => false]);

    $page->assertNoJavaScriptErrors();
})->with(['cancel', 'the x', 'escape', 'a backdrop click']);

test('a registered passkey closes the dialog and shows in the table without a reload or a toast', function (): void {
    $this->skipUnlessFeature(Feature::Passkeys);
    $user = User::factory()->create();
    $this->actingAs($user);
    $page = visitSecurityTabWithFreshPassword();

    expect($page->script(passkeyModalScript(<<<'SCRIPT'
        await stubPasskeys();
        window.samePage = true;
        window.toasts = 0;
        document.addEventListener('toast-show', () => window.toasts++);
        plusButton().click();

        return await until(opened);
        SCRIPT)))->toBeTrue();

    $user->passkeys()->create([
        'name' => 'Work laptop',
        'credential_id' => str()->random(32),
        'credential' => ['id' => 'credential'],
    ]);

    $result = $page->script(passkeyModalScript(<<<'SCRIPT'
        modal().querySelector('[type="submit"]').click();

        const shown = await until(() => ! modal() && document.body.innerText.includes('Work laptop'));

        return {
            shown,
            closed: dialog()?.open !== true,
            samePage: window.samePage === true,
            toasts: window.toasts,
        };
        SCRIPT));

    expect($result)->toBe(['shown' => true, 'closed' => true, 'samePage' => true, 'toasts' => 0]);

    $page->assertNoJavaScriptErrors();
});
