<?php

declare(strict_types=1);

use App\Domain\User\Support\SmsCode;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Support\Facades\Event;

test('an issued code has six digits', function (): void {
    expect(SmsCode::forVerification('+60123456789')->issue())->toMatch('/^\d{6}$/');
});

test('a code is unused from its issue until it passes', function (): void {
    $sms = SmsCode::forVerification('+60123456789');

    expect($sms->unused())->toBeFalse();

    $code = $sms->issue();

    expect($sms->unused())->toBeTrue()
        ->and($sms->check($code))->toBeTrue()
        ->and($sms->unused())->toBeFalse()
        ->and($sms->check($code))->toBeFalse();
});

test('a wrong code does not pass', function (): void {
    $sms = SmsCode::forVerification('+60123456789');
    $code = $sms->issue();

    expect($sms->check(wrongSmsCode($code)))->toBeFalse()
        ->and($sms->check($code))->toBeTrue();
});

test('a code stops working after five wrong tries', function (): void {
    $sms = SmsCode::forVerification('+60123456789');
    $code = $sms->issue();

    foreach (range(1, 5) as $try) {
        $sms->check(wrongSmsCode($code));
    }

    expect($sms->unused())->toBeFalse()
        ->and($sms->check($code))->toBeFalse();
});

test('a code works for ten minutes', function (): void {
    $sms = SmsCode::forVerification('+60123456789');
    $code = $sms->issue();

    $this->travel(9)->minutes();
    expect($sms->unused())->toBeTrue();

    $this->travel(1)->minutes();
    expect($sms->unused())->toBeFalse()
        ->and($sms->check($code))->toBeFalse();
});

test('a wrong try does not give the code more time', function (): void {
    $sms = SmsCode::forVerification('+60123456789');
    $code = $sms->issue();

    $this->travel(9)->minutes();
    $sms->check(wrongSmsCode($code));
    $this->travel(1)->minutes();

    expect($sms->check($code))->toBeFalse();
});

test('a new code replaces the old one', function (): void {
    $sms = SmsCode::forVerification('+60123456789');
    $old = $sms->issue();

    do {
        $new = $sms->issue();
    } while ($new === $old);

    expect($sms->check($old))->toBeFalse()
        ->and($sms->check($new))->toBeTrue();
});

test('a code works only for its number and its purpose', function (): void {
    $code = SmsCode::forVerification('+60123456789')->issue();

    expect(SmsCode::forVerification('+60198765432')->check($code))->toBeFalse()
        ->and(SmsCode::forPasswordReset('+60123456789')->check($code))->toBeFalse()
        ->and(SmsCode::forVerification('+60123456789')->check($code))->toBeTrue();
});

test('two checks at the same time each use up a try', function (): void {
    $sms = SmsCode::forVerification('+60123456789');
    $wrong = wrongSmsCode($sms->issue());
    $interleaved = false;
    Event::listen(CacheHit::class, function (CacheHit $event) use (&$interleaved, $sms, $wrong): void {
        if ($interleaved || ! str_starts_with($event->key, 'sms-code:')) {
            return;
        }

        $interleaved = true;
        $sms->check($wrong);
    });

    foreach (range(1, 4) as $try) {
        $sms->check($wrong);
    }

    expect($sms->unused())->toBeFalse();
});
