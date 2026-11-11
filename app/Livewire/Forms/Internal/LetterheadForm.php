<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Internal;

use App\Domain\Shared\Letter\Actions\SaveLetterhead;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Media\Enums\MediaCollection;
use App\Domain\Shared\Media\Rules\Uploads;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Components\FileUpload;
use App\Livewire\Form;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

class LetterheadForm extends Form
{
    public const array IMAGE_RULES = ['image', 'dimensions:min_width=2480', 'max:'.Field::MAX_IMAGE_KILOBYTES];

    #[Locked]
    public Organization $organization;

    #[Locked]
    public Letterhead $letterhead;

    public ?int $branch_id = null;

    public ?int $department_id = null;

    public string $name = '';

    /** @var list<array{uuid: string, name: string, size: int, mime_type: string, preview?: bool, upload?: ?string}> */
    public array $header = [];

    /** @var list<array{uuid: string, name: string, size: int, mime_type: string, preview?: bool, upload?: ?string}> */
    public array $footer = [];

    public function set(Organization $organization, Letterhead $letterhead): static
    {
        $this->organization = $organization;
        $this->letterhead = $letterhead;
        $this->branch_id = $letterhead->branch_id;
        $this->department_id = $letterhead->department_id;
        $this->name = $letterhead->name ?? '';
        $this->header = $letterhead->exists ? FileUpload::items($letterhead, MediaCollection::Header) : [];
        $this->footer = $letterhead->exists ? FileUpload::items($letterhead, MediaCollection::Footer) : [];

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', $this->inOrganization('branches')],
            'department_id' => ['nullable', 'integer', $this->inOrganization('departments')],
            'name' => ['required', 'string', 'max:255'],
            'header' => ['required', 'array', 'size:1', new Uploads(self::IMAGE_RULES)],
            'footer' => ['array', 'max:1', new Uploads(self::IMAGE_RULES)],
        ];
    }

    private function inOrganization(string $table): mixed
    {
        return Rule::exists($table, 'id')
            ->where('organization_id', $this->organization->id)
            ->withoutTrashed();
    }

    private function branch(): ?Branch
    {
        if ($this->branch_id === null) {
            return null;
        }

        return Branch::query()->findOrFail($this->branch_id);
    }

    private function department(): ?Department
    {
        if ($this->department_id === null) {
            return null;
        }

        return Department::query()->findOrFail($this->department_id);
    }

    public function save(): Letterhead
    {
        $this->validate();

        return SaveLetterhead::make()->execute($this->letterhead, $this->organization, $this->branch(), $this->department(), $this->name, $this->header, $this->footer);
    }
}
