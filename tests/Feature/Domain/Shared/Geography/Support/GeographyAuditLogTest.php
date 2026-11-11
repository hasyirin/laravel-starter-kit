<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\DeleteSubdistrict;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Domain\Shared\Geography\Support\GeographyAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    config()->set('address.locality', ['country' => null, 'state' => null, 'district' => null]);
    $this->country = Country::factory()->create();
    $this->state = State::factory()->for($this->country)->create();
    $this->district = District::factory()->for($this->state)->create();
    $this->postOffice = PostOffice::factory()->for($this->state)->create();
    $this->subdistrict = Subdistrict::factory()->for($this->district)->create();
    $this->postcode = Postcode::factory()->for($this->postOffice)->create();

    State::factory()->for(Country::factory()->create())->create();
});

function geographyAuditSubjects(Country|State|District|PostOffice $record): array
{
    return GeographyAuditLog::query($record)->get()
        ->map(fn (Activity $activity): string => $activity->subject_type.':'.$activity->subject_id)
        ->sort()
        ->values()
        ->all();
}

function geographySubjectKeys(Model ...$records): array
{
    return collect($records)->map(fn (Model $record): string => $record->getMorphClass().':'.$record->getKey())->sort()->values()->all();
}

test('the country log holds changes to the country and its states only', function (): void {
    expect(geographyAuditSubjects($this->country))->toBe(geographySubjectKeys($this->country, $this->state));
});

test('the state log holds changes to the state, its districts, and its cities only', function (): void {
    expect(geographyAuditSubjects($this->state))->toBe(geographySubjectKeys($this->state, $this->district, $this->postOffice));
});

test('the district log holds changes to the district and its subdistricts only', function (): void {
    expect(geographyAuditSubjects($this->district))->toBe(geographySubjectKeys($this->district, $this->subdistrict));
});

test('the city log holds changes to the city and its postcodes only', function (): void {
    expect(geographyAuditSubjects($this->postOffice))->toBe(geographySubjectKeys($this->postOffice, $this->postcode));
});

test('the log lists the newest entry first', function (): void {
    $this->country->update(['name' => 'Renamed']);

    expect(GeographyAuditLog::query($this->country)->first()->event)->toBe('updated');
});

test('entries of deleted children stay in the log', function (): void {
    DeleteSubdistrict::make()->execute($this->subdistrict);

    $deleted = GeographyAuditLog::query($this->district)->where('event', 'deleted')->get();

    expect($deleted->pluck('subject_type')->all())->toBe(['subdistrict']);
});

test('finding an entry of another record fails', function (): void {
    $foreign = Activity::query()->whereNotIn('id', GeographyAuditLog::query($this->country)->select('id'))->firstOrFail();

    expect(GeographyAuditLog::find($this->country, Activity::query()->whereMorphedTo('subject', $this->country)->firstOrFail()->id))->toBeInstanceOf(Activity::class);

    GeographyAuditLog::find($this->country, $foreign->id);
})->throws(ModelNotFoundException::class);
