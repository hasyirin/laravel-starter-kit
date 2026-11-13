<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;

class UpdatePreferences
{
    use Actionable;

    public function execute(User $user, string $timezone, string $language, string $locale): void
    {
        $user->update([
            'timezone' => $timezone,
            'language' => $language,
            'locale' => $locale,
        ]);
    }
}
