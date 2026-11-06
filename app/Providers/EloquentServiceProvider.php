<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Shared\Actor\Models\Action;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class EloquentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Model::shouldBeStrict(! app()->isProduction());

        Relation::enforceMorphMap([
            'action' => Action::class,
            'address' => Address::class,
            'branch' => Branch::class,
            'country' => Country::class,
            'department' => Department::class,
            'district' => District::class,
            'organization' => Organization::class,
            'position' => Position::class,
            'post_office' => PostOffice::class,
            'postcode' => Postcode::class,
            'profile' => Profile::class,
            'role' => Role::class,
            'state' => State::class,
            'subdistrict' => Subdistrict::class,
            'user' => User::class,
        ]);
    }
}
