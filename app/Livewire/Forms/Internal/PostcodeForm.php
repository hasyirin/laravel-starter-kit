<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Internal;

use App\Domain\Shared\Geography\Actions\SavePostcode;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Livewire\Form;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Locked;

class PostcodeForm extends Form
{
    #[Locked]
    public PostOffice $postOffice;

    #[Locked]
    public Postcode $postcode;

    public string $code = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public function set(PostOffice $postOffice, Postcode $postcode): static
    {
        $this->postOffice = $postOffice;
        $this->postcode = $postcode;
        $this->code = $postcode->code ?? '';
        $this->latitude = $postcode->latitude;
        $this->longitude = $postcode->longitude;

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
                Rule::unique(Postcode::class, 'code')
                    ->where('post_office_id', $this->postOffice->id)
                    ->ignore($this->postcode->id)
                    ->unless($this->postcode->exists, fn (Unique $rule): Unique => $rule->withoutTrashed()),
            ],
            'latitude' => [Rule::requiredIf($this->longitude !== null), 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => [Rule::requiredIf($this->latitude !== null), 'nullable', 'numeric', 'between:-180,180'],
        ];
    }

    private function coordinate(?float $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return round($value, 6);
    }

    public function save(): Postcode
    {
        $this->code = str($this->code)->squish()->upper()->value();

        $this->validate();

        return SavePostcode::make()->execute(
            postOffice: $this->postOffice,
            postcode: $this->postcode,
            code: $this->code,
            latitude: $this->coordinate($this->latitude),
            longitude: $this->coordinate($this->longitude),
        );
    }
}
