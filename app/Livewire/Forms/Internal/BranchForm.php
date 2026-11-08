<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Internal;

use App\Domain\Shared\Organization\Actions\SaveBranch;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Form;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

class BranchForm extends Form
{
    #[Locked]
    public Organization $organization;

    #[Locked]
    public ?Branch $branch = null;

    public string $code = '';

    public string $name = '';

    public function set(Organization $organization, ?Branch $branch = null): static
    {
        $this->organization = $organization;
        $this->branch = $branch;
        $this->code = $branch->code ?? '';
        $this->name = $branch->name ?? '';

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('branches', 'code')
                    ->where('organization_id', $this->organization->id)
                    ->withoutTrashed()
                    ->ignore($this->branch?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function save(): Branch
    {
        $this->validate();

        return SaveBranch::make()->execute($this->organization, $this->branch ?? new Branch, $this->code, $this->name);
    }
}
