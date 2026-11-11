<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Media\Actions\SyncMedia;
use App\Domain\Shared\Media\Enums\MediaCollection;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveLetterhead
{
    use Actionable;

    /**
     * @param  list<array{uuid: string, name: string, size: int, mime_type: string, preview?: bool, upload?: ?string}>  $headerItems
     * @param  list<array{uuid: string, name: string, size: int, mime_type: string, preview?: bool, upload?: ?string}>  $footerItems
     */
    public function execute(Letterhead $letterhead, Organization $organization, ?Branch $branch, ?Department $department, string $name, array $headerItems, array $footerItems): Letterhead
    {
        $letterhead->organization()->associate($organization);
        $letterhead->branch()->associate($branch);
        $letterhead->department()->associate($department);
        $letterhead->fill(['name' => $name]);

        try {
            DB::transaction(function () use ($letterhead, $headerItems, $footerItems): void {
                $letterhead->save();

                SyncMedia::make()->execute($letterhead, $headerItems, MediaCollection::Header);
                SyncMedia::make()->execute($letterhead, $footerItems, MediaCollection::Footer);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['placement' => __('letters/letterheads.placement_taken')]);
        }

        return $letterhead;
    }
}
