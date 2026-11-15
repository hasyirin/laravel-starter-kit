<?php

declare(strict_types=1);

namespace App\Livewire\Forms\System;

use App\Domain\Shared\Api\Actions\IssueApiToken;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Support\IssuedApiToken;
use App\Livewire\Form;
use Illuminate\Validation\Rule;

class ApiTokenForm extends Form
{
    /** @var list<string> */
    public array $scopes = [];

    public bool $never_expires = false;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scopes' => ['array'],
            'scopes.*' => ['string', Rule::in(config()->array('system.api.scopes'))],
            'never_expires' => ['boolean'],
        ];
    }

    public function save(ApiClient $apiClient): IssuedApiToken
    {
        $this->validate();

        return IssueApiToken::make()->execute($apiClient, $this->scopes, $this->never_expires);
    }
}
