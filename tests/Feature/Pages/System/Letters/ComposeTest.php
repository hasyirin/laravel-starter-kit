<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Models\AuditEntry;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\Components\FileUpload;
use App\Livewire\Pages\System\Letters\Compose;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Tests\Fixtures\Models\Permit;
use Tests\Fixtures\Policies\PermitPolicy;
use Tests\Fixtures\Support\PermitLetter;
use Tests\Fixtures\Support\PermitManualLetter;
use Tests\Fixtures\Support\PermitSignedLetter;

beforeEach(function (): void {
    Storage::fake('media');
    Pdf::fake();
    $this->seed(PermissionSeeder::class);
    Schema::create('permits', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->boolean('hidden')->default(false);
        $table->boolean('locked')->default(false);
        $table->timestampsTz();
        $table->softDeletesTz();
    });
    Relation::morphMap(['permit' => Permit::class]);
    Gate::policy(Permit::class, PermitPolicy::class);
    config(['system.letters.types' => ['manual' => PermitManualLetter::class, 'plain' => PermitLetter::class, 'signed' => PermitSignedLetter::class]]);

    $this->organization = Organization::factory()->create();
    $this->admin = User::factory()->create();
    Profile::factory()->for($this->organization)->for($this->admin)->create();
    grantPermissions($this->admin, 'system.access', 'system.letters.compose');
    $this->actingAs($this->admin);
    $this->permit = Permit::query()->create(['name' => 'Open']);
});

function compose(string $type = 'manual'): Testable
{
    return Livewire::test(Compose::class)->set('form.type', $type);
}

function manualEntry(): AuditEntry
{
    return AuditEntry::query()->where('event', 'printed-manual-letter')->sole();
}

test('users without system.letters.compose cannot open the page', function (): void {
    $user = User::factory()->create();
    grantPermissions($user, 'system.access');

    $this->actingAs($user)->get(route('system.letters.compose'))->assertForbidden();
});

test('users with system.letters.compose can open the page', function (): void {
    $this->get(route('system.letters.compose'))->assertOk()->assertSeeLivewire(Compose::class);
});

test('a user without the permission cannot run an action', function (): void {
    $component = compose();
    $user = User::factory()->create();
    grantPermissions($user, 'system.access');

    $this->actingAs($user);

    $component->call('print')->assertForbidden();
});

test('a blank start gives empty fields', function (): void {
    compose()->assertSet('start', 'blank')
        ->assertSet('form.data.title', null)
        ->assertSet('form.extra.remark', null)
        ->assertSet('form.reference_number', '');
});

test('a source start fills the mapping', function (): void {
    compose()->set('start', 'source')->set('sourceId', (string) $this->permit->id)
        ->assertSet('form.data.title', 'Open');
});

test('a source start without the view ability of the source is refused', function (): void {
    $hidden = Permit::query()->create(['name' => 'Secret', 'hidden' => true]);

    compose()->set('start', 'source')->set('sourceId', (string) $hidden->id)->assertForbidden();
});

test('the source picker lists only sources the admin can view, found by id or name', function (): void {
    Permit::query()->create(['name' => 'Secret', 'hidden' => true]);
    $other = Permit::query()->create(['name' => 'Harbour']);

    $component = compose()->set('start', 'source');

    expect(array_values($component->instance()->sources()))->toBe(["Open (#{$this->permit->id})", "Harbour (#{$other->id})"]);

    expect(array_values($component->set('sourceSearch', 'harb')->instance()->sources()))->toBe(["Harbour (#{$other->id})"])
        ->and(array_keys($component->set('sourceSearch', (string) $this->permit->id)->instance()->sources()))->toBe([$this->permit->id]);
});

test('a letter start fills the print details and the extra values of the letter', function (): void {
    $signer = User::factory()->create(['name' => 'Siti']);
    Profile::factory()->for($this->organization)->for($signer)->for(Position::factory()->for($this->organization)->create())->create();
    $letter = $this->permit->letters()->create(['type' => 'manual', 'date' => '2026-03-04', 'reference_number' => 'REF-1', 'extra' => ['remark' => 'Reminder']]);

    compose()->set('start', 'letter')->set('sourceId', (string) $this->permit->id)->set('letterId', (string) $letter->id)
        ->assertSet('form.reference_number', 'REF-1')
        ->assertSet('form.date', CarbonImmutable::parse('2026-03-04'))
        ->assertSet('form.extra.remark', 'Reminder')
        ->assertSet('form.data.title', 'Open');
});

test('a print downloads the letter and writes one audit entry, no letter row, no mark', function (): void {
    compose()->set('form.reference_number', 'REF-9')
        ->set('form.data.title', 'Permit title')
        ->set('form.extra.remark', 'Reminder')
        ->call('print')
        ->assertHasNoErrors()
        ->assertFileDownloaded('ref-9.pdf');

    expect(data_get(manualEntry()->properties, 'data.title'))->toBe('Permit title')
        ->and(data_get(manualEntry()->properties, 'extra.remark'))->toBe('Reminder')
        ->and(Letter::query()->withTrashed()->count())->toBe(0);
    Pdf::assertDontSee('Draft');
});

test('a preview downloads a draft and writes no audit entry', function (): void {
    compose()->set('form.data.title', 'Permit title')->set('form.extra.remark', 'Reminder')->call('preview')->assertHasNoErrors();

    expect(AuditEntry::query()->where('event', 'printed-manual-letter')->count())->toBe(0);
    Pdf::assertSee('Draft');
});

test('a ticked group prints its section and an unticked one does not', function (): void {
    compose()->set('form.data.title', 'T')->set('form.extra.remark', 'R')
        ->set('form.extra._groups.guarantor', true)
        ->set('form.extra.guarantor.name', 'Ali')
        ->set('form.extra.guarantor.phone', '012')
        ->call('print')->assertHasNoErrors();
    Pdf::assertSee('Ali');

    compose()->set('form.data.title', 'T')->set('form.extra.remark', 'R')
        ->set('form.extra.guarantor.name', 'Budi')
        ->call('print')->assertHasNoErrors();
    Pdf::assertDontSee('Budi');
});

test('a group that is on with no filled child is refused', function (): void {
    compose()->set('form.data.title', 'T')->set('form.extra.remark', 'R')
        ->set('form.extra._groups.guarantor', true)
        ->call('print')
        ->assertHasErrors(['form.extra.guarantor']);

    expect(AuditEntry::query()->where('event', 'printed-manual-letter')->count())->toBe(0);
});

test('a missing required value shows an error on its field', function (): void {
    compose()->set('form.extra.remark', 'R')->call('print')->assertHasErrors(['form.data.title']);
});

test('an upload ends on the audit entry', function (): void {
    $item = Livewire::test(FileUpload::class, ['fileRules' => ['image']])->set('uploads', UploadedFile::fake()->image('stamp.png', 40, 40))->get('items')[0];

    compose()->set('form.data.title', 'T')->set('form.extra.remark', 'R')->set('form.extra.stamp', [$item])->call('print')->assertHasNoErrors();

    $entry = manualEntry();
    expect($entry->getMedia()->sole()->uuid)->toBe(data_get($entry->properties, 'extra.stamp'));
});

test('a signer must have a position and be in the list', function (): void {
    $withPosition = User::factory()->create(['name' => 'Siti']);
    Profile::factory()->for($this->organization)->for($withPosition)->for(Position::factory()->for($this->organization)->create())->create();
    $without = User::factory()->create();
    Profile::factory()->for($this->organization)->for($without)->create(['position_id' => null]);

    $component = compose('plain');

    expect(array_keys($component->instance()->signers()))->toBe([$withPosition->id]);
    $component->set('form.signer_id', $without->id)->call('print')->assertHasErrors(['form.signer_id']);
});

test('a required signer is checked', function (): void {
    compose('signed')->call('print')->assertHasErrors(['form.signer_id']);
});
