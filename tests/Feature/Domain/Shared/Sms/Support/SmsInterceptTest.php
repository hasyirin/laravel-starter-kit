<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use Illuminate\Support\Facades\Log;
use Tests\Fixtures\Gateways\FakeGateway;
use Tests\Fixtures\Notifications\TextMessage;

beforeEach(function (): void {
    config(['system.sms.intercept.enabled' => true, 'system.sms.intercept.phone' => '']);
    app()->instance(SmsGateway::class, $this->gateway = new FakeGateway);
});

test('sms go to their real numbers when the intercept is off', function (): void {
    config(['system.sms.intercept.enabled' => false]);
    $this->actingAs(User::factory()->create(['phone' => '+60111111111']));

    User::factory()->create(['phone' => '+60123456789'])->notify(new TextMessage('The office is closed today.'));

    expect($this->gateway->sent)->toBe([['phone' => '+60123456789', 'message' => 'The office is closed today.']]);
});

test('sms go to the signed-in user', function (): void {
    $this->actingAs(User::factory()->create(['phone' => '+60111111111']));

    User::factory()->create(['phone' => '+60123456789'])->notify(new TextMessage('The office is closed today.'));

    expect($this->gateway->sent)->toBe([['phone' => '+60111111111', 'message' => 'The office is closed today.']]);
});

test('sms go to the impersonator during impersonation', function (): void {
    Impersonation::begin(User::factory()->create(['phone' => '+60111111111']), 'https://example.test/start');
    $this->actingAs(User::factory()->create(['phone' => '+60122222222']));

    User::factory()->create(['phone' => '+60123456789'])->notify(new TextMessage('The office is closed today.'));

    expect($this->gateway->sent)->toBe([['phone' => '+60111111111', 'message' => 'The office is closed today.']]);
});

test('sms go to the fallback number when no user is signed in', function (): void {
    config(['system.sms.intercept.phone' => '+60199999999']);

    User::factory()->create(['phone' => '+60123456789'])->notify(new TextMessage('The office is closed today.'));

    expect($this->gateway->sent)->toBe([['phone' => '+60199999999', 'message' => 'The office is closed today.']]);
});

test('sms go to the fallback number when the signed-in user has no phone number', function (): void {
    config(['system.sms.intercept.phone' => '+60199999999']);
    $this->actingAs(User::factory()->withoutPhone()->create());

    User::factory()->create(['phone' => '+60123456789'])->notify(new TextMessage('The office is closed today.'));

    expect($this->gateway->sent)->toBe([['phone' => '+60199999999', 'message' => 'The office is closed today.']]);
});

test('sms are dropped when no user is signed in and there is no fallback number, and the log names the row, not the number', function (): void {
    Log::spy();

    User::factory()->create(['phone' => '+60123456789'])->notify(new TextMessage('The office is closed today.'));

    expect($this->gateway->sent)->toBe([]);

    Log::shouldHaveReceived('info')->once()->with('SMS intercept dropped an SMS because it has no phone number.', ['message' => Message::query()->sole()->id]);
});

test('the intercept is off in production, also when its setting has a wrong value', function (mixed $setting): void {
    config(['system.sms.intercept.enabled' => $setting]);
    app()->detectEnvironment(fn (): string => 'production');
    $this->actingAs(User::factory()->create(['phone' => '+60111111111']));

    User::factory()->create(['phone' => '+60123456789'])->notify(new TextMessage('The office is closed today.'));

    expect($this->gateway->sent)->toBe([['phone' => '+60123456789', 'message' => 'The office is closed today.']]);
})->with(['on' => [true], 'an empty value' => ['']]);
