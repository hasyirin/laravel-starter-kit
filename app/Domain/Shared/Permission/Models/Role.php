<?php

namespace App\Domain\Shared\Permission\Models;

use App\Domain\Shared\Common\Concerns\Models\InteractsWithActivityLogs;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithTimestamps;
use App\Domain\Shared\Permission\Observers\RoleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    public function delete(): ?bool
    {
        throw_if($this->is_system, LogicException::class, 'System roles cannot be deleted.');

        return parent::delete();
    }

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            ...parent::casts(),
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => $this->is_system ? (string) __("user/roles.$this->name") : $this->name);
    }
}
