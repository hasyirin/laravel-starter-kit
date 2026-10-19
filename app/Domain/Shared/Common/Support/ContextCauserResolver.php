<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Support;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\CauserResolver;

class ContextCauserResolver extends CauserResolver
{
    protected function getDefaultCauser(): ?Model
    {
        return parent::getDefaultCauser() ?? User::withTrashed()->whereKey(context()->get('user_id'))->first();
    }
}
