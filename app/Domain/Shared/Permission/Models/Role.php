<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Models;

use App\Domain\Shared\Common\Concerns\Models\InteractsWithActivityLogs;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithTimestamps;
use App\Domain\Shared\Permission\Observers\RoleObserver;
use App\Domain\User\Models\Profile;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use LogicException;

/**
 * @property string $name
 * @property string $guard_name
 * @property bool $is_system
 */
#[ObservedBy(RoleObserver::class)]
class Role extends \Spatie\Permission\Models\Role
{
    use InteractsWithActivityLogs;
    use InteractsWithTimestamps;

    protected $fillable = [
        'name',
        'guard_name',
    ];

    protected $attributes = [
        'is_system' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            ...parent::casts(),
        ];
    }

    public function delete(): ?bool
    {
        throw_if($this->is_system, LogicException::class, 'System roles cannot be deleted.');
        throw_if($this->isAssignedToProfiles(), LogicException::class, 'Roles assigned to profiles cannot be deleted.');

        return parent::delete();
    }

    /**
     * @param  Model|int|string|array<int, Model|int|string>|Collection<int, Model|int|string>  $models
     */
    public function syncModels(array|Collection|Model|int|string $models, ?string $modelClass = null): static
    {
        throw_if($this->isAssignedToProfiles(), LogicException::class, 'The roles of a profile cannot be changed.');

        return $this->getConnection()->transaction(fn (): static => parent::syncModels($models, $modelClass));
    }

    /**
     * @return MorphToMany<Model, $this>
     */
    protected function relationForModel(string $modelClass): MorphToMany
    {
        throw_if(is_a($modelClass, Profile::class, true), LogicException::class, 'The roles of a profile cannot be changed.');

        return parent::relationForModel($modelClass);
    }

    private function isAssignedToProfiles(): bool
    {
        return parent::relationForModel(Profile::class)->exists();
    }

    /**
     * @return Attribute<string, never>
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => $this->is_system ? (string) __("user/roles.$this->name") : $this->name);
    }
}
