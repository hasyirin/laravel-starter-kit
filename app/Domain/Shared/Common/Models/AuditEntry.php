<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Models;

use App\Domain\Shared\Common\Concerns\Models\InteractsWithMedia;
use App\Domain\Shared\Common\Contracts\Models\HasMedia;
use Spatie\Activitylog\Models\Activity;

class AuditEntry extends Activity implements HasMedia
{
    use InteractsWithMedia;
}
