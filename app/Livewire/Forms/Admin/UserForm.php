<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Admin;

use App\Domain\User\Concerns\PasswordValidationRules;
use App\Domain\User\Concerns\ProfileValidationRules;
use App\Domain\User\Models\User;
use App\Livewire\Form;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

class UserForm extends Form
{
    use PasswordValidationRules;
    use ProfileValidationRules;

    #[Locked]
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?int $organization_id = null;

    public ?int $branch_id = null;

    public ?int $department_id = null;

    public ?int $position_id = null;

    public function fillFromUser(User $user): void
    {
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;

        $profile = $user->profile;
        $this->organization_id = $profile?->organization_id;
        $this->branch_id = $profile?->branch_id;
        $this->department_id = $profile?->department_id;
        $this->position_id = $profile?->position_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $passwordRules = $this->passwordRules();

        if ($this->userId !== null) {
            $passwordRules[0] = 'nullable';
        }

        return [
            ...$this->profileRules($this->userId),
            'password' => $passwordRules,
            'organization_id' => ['nullable', 'integer', Rule::exists('organizations', 'id')->whereNull('deleted_at')],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->whereNull('deleted_at')->where('organization_id', $this->organization_id)],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')->where('organization_id', $this->organization_id)],
            'position_id' => [
                'nullable',
                'integer',
                Rule::exists('positions', 'id')->whereNull('deleted_at')->where('organization_id', $this->organization_id)->where('department_id', $this->department_id),
            ],
        ];
    }

    /**
     * @return array{organization_id: ?int, branch_id: ?int, department_id: ?int, position_id: ?int}
     */
    public function placement(): array
    {
        return [
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
        ];
    }

    public function passwordOrNull(): ?string
    {
        return $this->password === '' ? null : $this->password;
    }
}
