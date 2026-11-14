<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Enums\ApiCallDirection;

test('every direction has a label', function (ApiCallDirection $direction): void {
    expect($direction->label())->not->toStartWith('api/');
})->with(ApiCallDirection::cases());
