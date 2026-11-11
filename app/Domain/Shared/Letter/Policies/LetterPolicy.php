<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Policies;

use App\Domain\Shared\Letter\Enums\FieldType;
use App\Domain\Shared\Letter\Enums\SignerRequirement;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Letter\Support\LetterTypes;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class LetterPolicy
{
    public function view(User $user, Letter $letter): bool
    {
        return $this->allowedBySource($user, $letter, 'view');
    }

    public function print(User $user, Letter $letter): bool
    {
        return $this->allowedBySource($user, $letter, 'print');
    }

    public function create(User $user, Letter $letter): bool
    {
        $source = $letter->source;

        if ($source === null) {
            return false;
        }

        $key = $letter->getAttributes()['type'] ?? null;

        if (is_string($key)) {
            $type = LetterTypes::tryFind($key);

            return $type instanceof LetterType && $this->creates($user, $type, $source);
        }

        return collect(LetterTypes::forSource($source))->contains(fn (LetterType $type): bool => $this->creates($user, $type, $source));
    }

    public function update(User $user, Letter $letter): bool
    {
        return $this->allowedBySource($user, $letter, 'update');
    }

    public function delete(User $user, Letter $letter): bool
    {
        return $this->allowedBySource($user, $letter, 'delete');
    }

    private function creates(User $user, LetterType $type, Model $source): bool
    {
        $lockedRequired = collect($type->extraFields())->contains(
            fn (Field $field): bool => in_array($field->name, $type->lockedFields(), true) && ! $field->isOptional && $field->type !== FieldType::Group,
        );

        $signerRequired = $type->signer() === SignerRequirement::Required;

        if (! $type->allowedFor($user) || $lockedRequired || ($signerRequired && $user->cannot($type->sourceAbility('print'), $source))) {
            return false;
        }

        return $user->can($type->sourceAbility('create'), $source);
    }

    private function allowedBySource(User $user, Letter $letter, string $ability): bool
    {
        $source = $letter->source;

        if ($source === null) {
            return false;
        }

        $type = LetterTypes::tryFind($letter->type);

        if (! $type instanceof LetterType) {
            return match ($ability) {
                'view' => $user->can('view', $source),
                'delete' => $user->can('update', $source),
                default => false,
            };
        }

        if (! in_array($ability, ['view', 'delete'], true) && ! $type->allowedFor($user)) {
            return false;
        }

        return $user->can($type->sourceAbility($ability), $source);
    }
}
