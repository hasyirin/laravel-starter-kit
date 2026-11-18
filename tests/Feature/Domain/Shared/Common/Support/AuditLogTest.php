<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Common\Support\AuditLog;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\Fixtures\Models\Badge;
use Tests\Fixtures\Models\Charge;

beforeEach(function (): void {
    Schema::create('charges', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->nullableMorphs('billable');
        $table->timestampsTz();
        $table->softDeletesTz();
    });

    Relation::morphMap(['charge' => Charge::class]);
});

function billableChangeEntry(int $billableId): Activity
{
    return new Activity([
        'subject_type' => 'charge',
        'attribute_changes' => [
            'attributes' => ['billable_type' => 'user', 'billable_id' => $billableId],
            'old' => ['billable_type' => null, 'billable_id' => null],
        ],
    ]);
}

test('the record lookup finds records of a type by name, deleted ones too', function (): void {
    $ada = User::factory()->create(['name' => 'Ada Lovelace']);
    $adam = User::factory()->create(['name' => 'Adam Smith']);
    User::factory()->create(['name' => 'Grace Hopper']);
    $adam->delete();

    $records = AuditLog::records('user', 'ada');

    expect($records)->toBe([$ada->id => 'Ada Lovelace', $adam->id => 'Adam Smith']);
});

test('the record lookup names the picked records, deleted ones too', function (): void {
    $organization = Organization::factory()->create(['name' => 'Acme Holdings']);
    $closed = Organization::factory()->create(['name' => 'Closed Ltd']);
    $closed->delete();

    $names = AuditLog::recordNames('organization', [(string) $organization->id, (string) $closed->id]);

    expect($names)->toBe([$organization->id => 'Acme Holdings', $closed->id => 'Closed Ltd']);
});

test('records that a name does not pick out cannot be looked up', function (string $type): void {
    expect(AuditLog::nameColumn($type))->toBeNull()
        ->and(AuditLog::records($type, ''))->toBeEmpty();
})->with(['profile', 'action', 'an unknown type' => 'unknown']);

test('the causer types are the types that made changes, and none', function (): void {
    $this->actingAs(User::factory()->create());
    Organization::factory()->create();

    expect(AuditLog::causerTypes())->toBe(['user' => 'User', AuditLog::NO_CAUSER => 'None']);
});

test('a record is named by its first label, name, title, or email by default', function (): void {
    $organization = Organization::factory()->create(['name' => 'Acme Holdings']);
    $entry = Activity::query()->whereMorphedTo('subject', $organization)->firstOrFail();

    expect(AuditLog::subjectLabel($entry))->toBe('Organization Acme Holdings');
});

test('a record that defines its own audit label is named by it', function (): void {
    $badge = new Badge()->forceFill(['id' => 7, 'code' => 'ACME-01', 'name' => 'Acme Holdings']);

    $entry = new Activity(['subject_type' => 'badge', 'subject_id' => 7]);
    $entry->setRelation('subject', $badge);

    expect(AuditLog::subjectLabel($entry))->toBe('Badge ACME-01');
});

test('a search finds the changes that an API client made by its name', function (): void {
    activity()->causedBy(ApiClient::factory()->create(['name' => 'Billing']))->log('Synced');
    $query = Activity::query();

    AuditLog::search($query, 'billing');

    expect($query->pluck('description'))->toContain('Synced');
});

test('an API client that made a change is named as its causer', function (): void {
    activity()->causedBy(ApiClient::factory()->create(['name' => 'Billing']))->log('Synced');

    expect(AuditLog::causerName(Activity::query()->where('description', 'Synced')->sole()))->toBe('Billing');
});

test('a morph relation shows its type alias and its id after #', function (): void {
    $charge = Charge::query()->create(['name' => 'Wrong Charge']);

    $changes = AuditLog::changes(billableChangeEntry($charge->id));

    expect($changes->all())->toBe([
        ['field' => 'Billable Type', 'old' => '', 'new' => 'user'],
        ['field' => 'Billable', 'old' => '', 'new' => '#'.$charge->id],
    ]);
});

test('a morph relation shows without a query for a name', function (): void {
    $entry = billableChangeEntry(7);
    DB::enableQueryLog();

    AuditLog::changes($entry);

    expect(DB::getQueryLog())->toBeEmpty();
});

test('a changed list of rows shows one line per row with its values', function (): void {
    $apiClient = ApiClient::factory()->create(['allowed_ip_addresses' => [['address' => '203.0.113.10', 'description' => 'HQ firewall']]]);
    $apiClient->update(['allowed_ip_addresses' => [
        ['address' => '203.0.113.10', 'description' => 'HQ firewall'],
        ['address' => '198.51.100.0/24', 'description' => null],
    ]]);

    $change = AuditLog::changes(Activity::query()->whereMorphedTo('subject', $apiClient)->where('event', 'updated')->sole())->sole();

    expect($change['old'])->toBe('203.0.113.10 (HQ firewall)')
        ->and($change['new'])->toBe("203.0.113.10 (HQ firewall)\n198.51.100.0/24");
});

/**
 * @param  array<array-key, mixed>  $rows
 */
function auditedNewAllowedIpAddresses(array $rows): string
{
    $apiClient = ApiClient::factory()->create(['allowed_ip_addresses' => []]);
    $apiClient->update(['allowed_ip_addresses' => $rows]);

    return AuditLog::changes(Activity::query()->whereMorphedTo('subject', $apiClient)->where('event', 'updated')->sole())->sole()['new'];
}

test('a yes or no value in a changed row shows as Yes or No', function (): void {
    expect(auditedNewAllowedIpAddresses([['name' => 'x', 'active' => false], ['name' => 'y', 'active' => true]]))->toBe("x (No)\ny (Yes)");
});

test('a changed list of rows that are empty, plain lists, or start with no value shows as JSON', function (array $rows, string $json): void {
    expect(auditedNewAllowedIpAddresses($rows))->toBe($json);
})->with([
    'an empty row' => [[[]], '[[]]'],
    'two empty rows' => [[[], []], '[[],[]]'],
    'plain lists' => [[[1, 2], [3, 4]], '[[1,2],[3,4]]'],
    'a row that starts with no value' => [[['address' => null, 'description' => 'HQ']], '[{"address":null,"description":"HQ"}]'],
]);
