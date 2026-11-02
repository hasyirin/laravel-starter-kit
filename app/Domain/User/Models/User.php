<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use App\Domain\Shared\Actor\Contracts\Models\HasActorName;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithActivityLogs;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithMedia;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithTimestamps;
use App\Domain\Shared\Common\Contracts\Models\HasMedia;
use App\Domain\Shared\Permission\Concerns\HasRoles;
use App\Domain\User\Concerns\HasProfile;
use App\Domain\User\Factories\UserFactory;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\Models\Concerns\CausesActivity;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $timezone
 * @property string|null $locale
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class User extends Authenticatable implements HasActorName, HasMedia, MustVerifyEmail, PasskeyUser
{
    use CausesActivity;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    use HasProfile;
    use HasRoles;
    use InteractsWithActivityLogs;
    use InteractsWithMedia;
    use InteractsWithTimestamps;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'locale',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected $attributes = [
        'timezone' => null,
        'locale' => null,
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => str($value)->lower()->value());
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function initials(): string
    {
        $initials = str($this->name)->initials(true);

        return $initials->length() > 1
            ? $initials->substr(0, 1).$initials->substr(-1)
            : $initials->value();
    }

    public function getActorName(): string
    {
        return $this->name;
    }
}
