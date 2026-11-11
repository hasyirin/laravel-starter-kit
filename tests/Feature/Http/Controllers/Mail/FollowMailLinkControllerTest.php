<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Models\Mail;
use App\Domain\Shared\Mail\Models\MailLink;
use App\Domain\User\Models\User;

test('a link to a public page records a click and redirects', function (): void {
    $this->freezeSecond();
    $link = MailLink::factory()->create(['url' => 'https://example.org/guide']);

    $response = $this->get(route('mail.link', $link->token));

    $response->assertRedirect('https://example.org/guide');
    expect($link->refresh()->clicked_at)->toEqual(now())
        ->and($link->mail->clicked_at)->toEqual(now())
        ->and($link->mail->viewed_at)->toBeNull();
});

test('a link to a page behind a sign-in sends a guest to the login page first', function (): void {
    $link = MailLink::factory()->requiringAuth()->create();

    $response = $this->get(route('mail.link', $link->token));

    $response->assertRedirect(route('login'));
    expect($link->refresh()->clicked_at)->toBeNull()
        ->and(session('url.intended'))->toBe(route('mail.link', $link->token));
});

test('the signed-in recipient makes a view', function (): void {
    $this->freezeSecond();
    $user = User::factory()->create();
    $mail = Mail::factory()->create(['recipient_email' => 'other@example.com']);
    $mail->notifiable()->associate($user)->save();
    $link = MailLink::factory()->for($mail)->requiringAuth()->create();

    $response = $this->actingAs($user)->get(route('mail.link', $link->token));

    $response->assertRedirect(route('settings'));
    expect($link->refresh()->viewed_at)->toEqual(now())
        ->and($mail->refresh()->viewed_at)->toEqual(now());
});

test('a signed-in user whose address is the recipient makes a view', function (): void {
    $this->freezeSecond();
    $user = User::factory()->create(['email' => 'aisyah@example.com']);
    $link = MailLink::factory()->for(Mail::factory()->state(['recipient_email' => 'Aisyah@Example.com']))->requiringAuth()->create();

    $this->actingAs($user)->get(route('mail.link', $link->token));

    expect($link->refresh()->viewed_at)->toEqual(now());
});

test('a signed-in user who is not the recipient passes through without a view', function (): void {
    $link = MailLink::factory()->requiringAuth()->create();

    $response = $this->actingAs(User::factory()->create())->get(route('mail.link', $link->token));

    $response->assertRedirect(route('settings'));
    expect($link->refresh()->viewed_at)->toBeNull()
        ->and($link->clicked_at)->toBeNull();
});

test('an unknown link token is not found', function (): void {
    $this->get(route('mail.link', 'unknown'))->assertNotFound();
});

test('the link route limits repeated hits of one link', function (): void {
    $link = MailLink::factory()->create(['url' => 'https://example.org/guide']);

    foreach (range(1, 30) as $hit) {
        $this->get(route('mail.link', $link->token))->assertRedirect('https://example.org/guide');
    }

    $this->get(route('mail.link', $link->token))->assertTooManyRequests();
});
