<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\User\Support\Placement;
use Illuminate\Database\Eloquent\Builder;

class FindLetterhead
{
    use Actionable;

    public function execute(Placement $placement): ?Letterhead
    {
        if ($placement->organizationId === null) {
            return null;
        }

        return Letterhead::query()
            ->where('organization_id', $placement->organizationId)
            ->where(fn (Builder $query): Builder => $this->matching($query, 'branch_id', $placement->branchId))
            ->where(fn (Builder $query): Builder => $this->matching($query, 'department_id', $placement->departmentId))
            ->get()
            ->sortByDesc(fn (Letterhead $letterhead): int => ($letterhead->branch_id === null ? 0 : 2) + ($letterhead->department_id === null ? 0 : 1))
            ->first();
    }

    /**
     * @param  Builder<Letterhead>  $query
     * @return Builder<Letterhead>
     */
    private function matching(Builder $query, string $column, ?int $id): Builder
    {
        $query->whereNull($column);

        if ($id !== null) {
            $query->orWhere($column, $id);
        }

        return $query;
    }
}
