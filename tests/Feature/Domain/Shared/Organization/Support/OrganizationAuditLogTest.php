<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Actions\DeleteDepartment;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Organization\Support\OrganizationAuditLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->department = Department::factory()->for($this->organization)->create();
    $this->branch = Branch::factory()->for($this->organization)->create();
    $this->position = Position::factory()->for($this->organization)->forDepartment($this->department)->create();
    $this->loosePosition = Position::factory()->for($this->organization)->create();

    $other = Organization::factory()->create();
    Department::factory()->for($other)->create();
});

function auditSubjects(Organization|Department|Branch $record): array
{
    return OrganizationAuditLog::query($record)->get()
        ->map(fn (Activity $activity): string => $activity->subject_type.':'.$activity->subject_id)
        ->sort()
        ->values()
        ->all();
}

function subjectKeys(Organization|Department|Branch|Position ...$records): array
{
    return collect($records)->map(fn ($record): string => $record->getMorphClass().':'.$record->id)->sort()->values()->all();
}

test('the organization log holds changes to the organization and everything under it', function (): void {
    expect(auditSubjects($this->organization))->toBe(subjectKeys($this->organization, $this->department, $this->branch, $this->position, $this->loosePosition));
});

test('the department log holds changes to the department and its positions only', function (): void {
    expect(auditSubjects($this->department))->toBe(subjectKeys($this->department, $this->position));
});

test('the branch log holds changes to the branch only', function (): void {
    expect(auditSubjects($this->branch))->toBe(subjectKeys($this->branch));
});

test('the log lists the newest entry first', function (): void {
    $this->organization->update(['name' => 'Renamed']);

    expect(OrganizationAuditLog::query($this->organization)->first()->event)->toBe('updated');
});

test('entries of deleted children stay in the log', function (): void {
    DeleteDepartment::make()->execute($this->department);

    $deleted = OrganizationAuditLog::query($this->organization)->where('event', 'deleted')->get();

    expect($deleted->pluck('subject_type')->sort()->values()->all())->toBe(['department', 'position']);
});

test('finding an entry of another organization fails', function (): void {
    $foreign = Activity::query()->whereNotIn('id', OrganizationAuditLog::query($this->organization)->select('id'))->firstOrFail();

    expect(OrganizationAuditLog::find($this->organization, Activity::query()->whereMorphedTo('subject', $this->organization)->firstOrFail()->id))->toBeInstanceOf(Activity::class);

    OrganizationAuditLog::find($this->organization, $foreign->id);
})->throws(ModelNotFoundException::class);
