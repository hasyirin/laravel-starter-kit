<?php

namespace App\Domain\Shared\Permission\Models;

use App\Domain\Shared\Common\Concerns\Models\InteractsWithActivityLogs;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithTimestamps;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @property string $name
 * @property string $guard_name
 * @property bool $is_internal
 */
class Role extends \Spatie\Permission\Models\Role
{
    use InteractsWithActivityLogs;
    use InteractsWithTimestamps;

    protected $fillable = [
        'name',
        'guard_name',
        'is_internal',
    ];

    protected $attributes = [
        'is_internal' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            ...parent::casts(),
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => $this->is_internal ? (string) __("user/roles.$this->name") : $this->name);
    }
}
