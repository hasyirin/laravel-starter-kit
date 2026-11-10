<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Letter\Support\LetterheadView;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\User\Support\Placement;

class ResolveLetterhead
{
    use Actionable;

    public function execute(LetterType $type, ?Placement $signer, Placement $printer): Letterhead|LetterheadView|null
    {
        $fromType = $type->letterhead();

        if ($fromType !== null) {
            return $fromType;
        }

        if ($signer instanceof Placement) {
            $fromSigner = FindLetterhead::make()->execute($signer);

            if ($fromSigner instanceof Letterhead) {
                return $fromSigner;
            }
        }

        return FindLetterhead::make()->execute($printer);
    }
}
