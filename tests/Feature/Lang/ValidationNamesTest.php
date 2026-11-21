<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Support\AuditLog;

test('a validation message names its field in the language of the page', function (string $language, string $field, string $message): void {
    app()->setLocale($language);

    expect(validator([], [$field => 'required'])->errors()->first($field))->toBe($message);
})->with([
    'English, a plain field' => ['en', 'email', 'The email field is required.'],
    'English, a record field' => ['en', 'department_id', 'The department field is required.'],
    'English, the country of an address' => ['en', 'country_id', 'The country field is required.'],
    'Malay, a plain field' => ['ms', 'email', 'Medan e-mel diperlukan.'],
    'Malay, a record field' => ['ms', 'department_id', 'Medan jabatan diperlukan.'],
    'Malay, the signer' => ['ms', 'signer_id', 'Medan penandatangan diperlukan.'],
    'Malay, an uploaded file' => ['ms', 'file', 'Medan fail diperlukan.'],
]);

test('an item rule failure names the role or permission item in the language of the page', function (string $language, string $field, string $message): void {
    app()->setLocale($language);

    $errors = validator([$field => ['x']], ["{$field}.*" => 'in:a'])->errors();

    expect($errors->first("{$field}.0"))->toBe($message);
})->with([
    'English, a role' => ['en', 'roles', 'The selected role is invalid.'],
    'English, a permission' => ['en', 'permissions', 'The selected permission is invalid.'],
    'Malay, a role' => ['ms', 'roles', 'Medan peranan yang dipilih tidak sah.'],
    'Malay, a permission' => ['ms', 'permissions', 'Medan akses yang dipilih tidak sah.'],
]);

test('the audit log labels a field with its validation name, with a capital', function (string $language, string $field, string $label): void {
    app()->setLocale($language);

    expect(AuditLog::fieldLabel($field))->toBe($label);
})->with([
    'English' => ['en', 'permissions', 'Permissions'],
    'Malay' => ['ms', 'permissions', 'Akses'],
    'Malay, a record field' => ['ms', 'department_id', 'Jabatan'],
    'Malay, the country of an address' => ['ms', 'country_id', 'Negara'],
    'Malay, the state of an address' => ['ms', 'state_id', 'Negeri'],
    'Malay, the type of an address' => ['ms', 'type', 'Jenis'],
    'a field without a name' => ['ms', 'is_owner', 'Is Owner'],
]);
