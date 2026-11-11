<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Internal;

use App\Domain\Shared\Geography\Actions\SaveSubdistrict;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Livewire\Form;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Locked;

class SubdistrictForm extends Form
{
    #[Locked]
    public District $district;

    #[Locked]
    public Subdistrict $subdistrict;

    public string $code = '';

    public string $name = '';

    public function set(District $district, Subdistrict $subdistrict): static
    {
        $this->district = $district;
        $this->subdistrict = $subdistrict;
        $this->code = $subdistrict->code ?? '';
        $this->name = $subdistrict->name ?? '';

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
                'max:255',
                Rule::unique(Subdistrict::class, 'code')
                    ->where('district_id', $this->district->id)
                    ->ignore($this->subdistrict->id)
                    ->unless($this->subdistrict->exists, fn (Unique $rule): Unique => $rule->withoutTrashed()),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function save(): Subdistrict
    {
        $this->code = str($this->code)->squish()->value();
        $this->name = str($this->name)->squish()->value();

        $this->validate();

        return SaveSubdistrict::make()->execute($this->district, $this->subdistrict, $this->code, $this->name);
    }
}
