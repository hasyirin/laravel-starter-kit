<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Support\PdfFile;

test('the file name keeps the separators of the reference number and falls back when the slug is empty', function (?string $referenceNumber, string $name): void {
    expect(PdfFile::named($referenceNumber, 'letter-7', 'content')->name)->toBe($name);
})->with([
    'slashes' => ['MBSJ/LIC/2026/0007', 'mbsj-lic-2026-0007.pdf'],
    'backslashes' => ['MBSJ\\LIC\\0007', 'mbsj-lic-0007.pdf'],
    'symbols only' => ['///', 'letter-7.pdf'],
    'no reference number' => [null, 'letter-7.pdf'],
]);
