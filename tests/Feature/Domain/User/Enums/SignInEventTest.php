<?php

declare(strict_types=1);

use App\Domain\User\Enums\SignInEvent;

test('every event has a label', function (SignInEvent $event): void {
    expect($event->label())->not->toStartWith('user/');
})->with(SignInEvent::cases());
