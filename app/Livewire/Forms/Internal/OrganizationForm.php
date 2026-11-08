<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Internal;

use App\Domain\Shared\Organization\Actions\SaveOrganization;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Form;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

class OrganizationForm extends Form
{
    #[Locked]
    public ?Organization $organization = null;

    public string $code = '';

    public string $name = '';

    public function set(Organization $organization): static
    {
        $this->organization = $organization;
        $this->code = $organization->code;
        $this->name = $organization->name;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('organizations', 'code')->withoutTrashed()->ignore($this->organization?->id)],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function save(): Organization
    {
        $this->validate();

        return SaveOrganization::make()->execute($this->organization ?? new Organization, $this->code, $this->name);
    }
}
