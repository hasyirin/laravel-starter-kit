<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System\ApiClient;

use App\Domain\Shared\Api\Actions\RotateApiToken;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Livewire\Forms\System\ApiTokenForm;
use App\Livewire\Modal;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class ApiTokenModal extends Modal
{
    #[Locked]
    public ApiClient $apiClient;

    #[Locked]
    public ?ApiToken $apiToken = null;

    public ApiTokenForm $form;

    private ?string $plainToken = null;

    public static function event(): string
    {
        return static::class.':saved';
    }

    #[On('show')]
    public function show(?int $id = null): void
    {
        $this->authorize('update', $this->apiClient);

        $this->apiToken = $id === null ? null : $this->apiClient->tokens()->findOrFail($id);
        abort_if($this->apiToken?->revoked === true || $this->apiToken?->expired === true, 403);

        $this->form->reset();

        parent::show();
    }

    public function save(): void
    {
        abort_unless($this->showing, 403);

        $this->authorize('update', $this->apiClient);

        $issued = $this->apiToken instanceof ApiToken
            ? RotateApiToken::make()->execute($this->apiToken)
            : $this->form->save($this->apiClient);

        $this->plainToken = $issued->plainToken;

        $this->dispatch(self::event());
    }

    public function render(): View
    {
        $scopes = config()->array('system.api.scopes');

        return view('livewire.pages.system.api-client.api-token-modal', [
            'plainToken' => $this->plainToken,
            'scopes' => collect($scopes)->combine($scopes)->all(),
        ]);
    }
}
