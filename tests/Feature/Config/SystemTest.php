<?php

declare(strict_types=1);

/**
 * @param  array<string, string>  $values
 */
function smsInterceptSetting(array $values): mixed
{
    $server = $_SERVER;
    unset($_SERVER['SMS_INTERCEPT'], $_SERVER['MAIL_INTERCEPT']);
    $_SERVER = [...$_SERVER, ...$values];

    try {
        return (require config_path('system.php'))['sms']['intercept']['enabled'];
    } finally {
        $_SERVER = $server;
    }
}

test('the sms intercept follows the mail intercept when a server does not set it', function (string $mail, bool $enabled): void {
    expect(smsInterceptSetting(['MAIL_INTERCEPT' => $mail]))->toBe($enabled);
})->with([
    'the mail intercept is on' => ['true', true],
    'the mail intercept is off' => ['false', false],
]);

test('a server can turn the sms intercept off while the mail intercept is on', function (): void {
    expect(smsInterceptSetting(['MAIL_INTERCEPT' => 'true', 'SMS_INTERCEPT' => 'false']))->toBeFalse();
});
