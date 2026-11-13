<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Models\User;

test('registration is off by default', function (): void {
    $this->skipIfFeature(Feature::Registration);

    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});

test('the login page hides the sign-up link when registration is off', function (): void {
    $this->skipIfFeature(Feature::Registration);

    $this->get(route('login'))->assertOk()->assertDontSee(__('auth/login.sign_up'));
});

describe('with registration on', function (): void {
    beforeEach(function (): void {
        $this->enableFeature(Feature::Registration);
    });

    test('the login page shows the sign-up link', function (): void {
        $this->get(route('login'))->assertSee(__('auth/login.sign_up'));
    });

    test('registration screen can be rendered', function (): void {
        $response = $this->get(route('register'));

        $response->assertOk();
    });

    test('a new user gets the language of the registration page', function (): void {
        $this->withCookie('language', 'ms')->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '+60123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        expect(User::query()->where('email', 'test@example.com')->sole()->language)->toBe('ms');
    });

    test('new users can register', function (): void {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '+60123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    });

    test('users cannot register with an email that another user has', function (): void {
        User::factory()->create(['email' => 'test@example.com']);

        $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '+60123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    test('users can register again with the email of a deleted account', function (): void {
        User::factory()->create(['email' => 'test@example.com'])->delete();

        $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '+60123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticated();

        expect(User::withTrashed()->where('email', 'test@example.com')->count())->toBe(2);
    });

    test('new users register with an unverified phone number', function (): void {
        $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '+60123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'test@example.com')->sole();

        expect($user->phone)->toBe('+60123456789')
            ->and($user->phone_verified_at)->toBeNull();
    });

    test('new users must give a phone number in international format', function (?string $phone): void {
        $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => $phone,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
    })->with(['no number' => [null], 'a local number' => ['012-345 6789']]);

    test('a guest can send the registration form five times a minute', function (): void {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('register.store'))->assertSessionHasErrors();
        }

        $this->post(route('register.store'))->assertTooManyRequests();
    });
});
