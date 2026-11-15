<?php

declare(strict_types=1);

namespace App\Livewire\Pages\System\ApiClient;

use App\Domain\Shared\Api\Actions\RotateApiToken;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Livewire\Forms\System\ApiTokenForm;
use App\Livewire\Modal;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class ApiTokenModal extends Modal
{
    #[Locked]
    public ApiClient $apiClient;

    public ApiTokenForm $form;

    private ?string $plainToken = null;

    public static function event(): string
    {
        return static::class.':saved';
    }

    #[On('show')]
    public function show(): void
    {
        $this->authorize('update', $this->apiClient);

        $this->form->reset();

        parent::show();
    }

    #[On('rotate')]
    public function rotate(int $id): void
    {
        $this->authorize('update', $this->apiClient);

        $apiToken = $this->apiClient->tokens()->findOrFail($id);
        abort_if($apiToken->revoked || $apiToken->expired, 403);

        try {
            $this->plainToken = RotateApiToken::make()->execute($apiToken)->plainToken;
        } catch (ValidationException $validationException) {
            Flux::toast(variant: 'danger', text: (string) collect($validationException->errors())->flatten()->first());

            return;
        }

        parent::show();

        $this->dispatch(self::event());
    }

    public function save(): void
    {
        abort_unless($this->showing, 403);

        $this->authorize('update', $this->apiClient);

        $this->plainToken = $this->form->save($this->apiClient)->plainToken;

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
