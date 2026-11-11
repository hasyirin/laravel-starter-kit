<?php

declare(strict_types=1);

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Letter\Enums\LetterMedia;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Letter\Support\CarbonCopy;
use App\Domain\Shared\Letter\Support\LetterheadView;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\Components\FileUpload;
use App\Livewire\Components\LetterModal;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Fixtures\Models\Permit;
use Tests\Fixtures\Policies\PermitPolicy;
use Tests\Fixtures\Policies\ToggledLetterPolicy;
use Tests\Fixtures\Support\NoticeLetter;
use Tests\Fixtures\Support\PermitExtraLetter;
use Tests\Fixtures\Support\PermitGuardedLetter;
use Tests\Fixtures\Support\PermitLabelledLetter;
use Tests\Fixtures\Support\PermitLetter;
use Tests\Fixtures\Support\PermitLockedLetter;
use Tests\Fixtures\Support\PermitOverrideLetter;
use Tests\Fixtures\Support\PermitPlainLetter;
use Tests\Fixtures\Support\PermitRestrictedLetter;
use Tests\Fixtures\Support\PermitSignedLetter;
use Tests\Fixtures\Support\PermitStrictLetter;
use Tests\Fixtures\Support\PermitTableLetter;
use Tests\Fixtures\Support\PermitViewHeadLetter;

beforeEach(function (): void {
    Storage::fake('media');
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
    config(['system.letters.types' => [
        'plain' => PermitPlainLetter::class,
        'permit' => PermitLetter::class,
        'extra' => PermitExtraLetter::class,
        'override' => PermitOverrideLetter::class,
        'signed' => PermitSignedLetter::class,
        'locked' => PermitLockedLetter::class,
        'restricted' => PermitRestrictedLetter::class,
        'strict' => PermitStrictLetter::class,
        'guarded' => PermitGuardedLetter::class,
        'table' => PermitTableLetter::class,
        'viewhead' => PermitViewHeadLetter::class,
        'notice' => NoticeLetter::class,
    ]]);

    $this->organization = Organization::factory()->create();
    $this->actor = User::factory()->create();
    Profile::factory()->for($this->organization)->for($this->actor)->create();
    $this->actingAs($this->actor);
    $this->permit = Permit::query()->create(['name' => 'Open']);
});

function modalSigner(string $name = 'Signer', ?Organization $organization = null, bool $position = true): User
{
    $user = User::factory()->create(['name' => $name]);
    $organization ??= test()->organization;
    $position = $position ? Position::factory()->for($organization)->create() : null;
    Profile::factory()->for($organization)->for($user)->create(['position_id' => $position?->id]);

    return $user;
}

function openModal(?Letter $letter = null): Testable
{
    return Livewire::test(LetterModal::class, ['source' => test()->permit])->call('show', $letter?->id);
}

function savedLetter(string $type, array $attributes = []): Letter
{
    return test()->permit->letters()->create(['type' => $type, 'date' => '2026-03-04', ...$attributes]);
}

test('the type list has only the types for this source that the user may use', function (): void {
    $types = openModal()->instance()->types();

    expect(array_keys($types))->toBe(['plain', 'permit', 'extra', 'override', 'signed', 'guarded', 'table', 'viewhead']);
});

test('a new letter takes today in the system timezone', function (): void {
    config(['system.timezone' => 'Asia/Kuala_Lumpur']);
    $this->travelTo(CarbonImmutable::parse('2026-03-04 23:30:00', 'UTC'));

    expect(openModal()->get('form.date')?->toDateString())->toBe('2026-03-05');
});

test('the type list shows the label of each letter type', function (): void {
    config(['system.letters.types' => ['permit' => PermitLetter::class, 'labelled' => PermitLabelledLetter::class]]);

    expect(openModal()->instance()->types())->toBe(['permit' => 'Permit', 'labelled' => 'Permit Approval']);
});

test('the only type is chosen at once', function (): void {
    config(['system.letters.types' => ['permit' => PermitLetter::class]]);

    openModal()->assertSet('form.type', 'permit');
});

test('the signer list has only users with a position in the organization of the printer', function (): void {
    $inside = modalSigner('Inside');
    modalSigner('No Position', position: false);
    modalSigner('Outside', Organization::factory()->create());
    modalSigner('Excluded Signer');

    $component = openModal()->set('form.type', 'signed');

    expect(array_values($component->instance()->signers()))->toBe(['Inside'])
        ->and(array_keys($component->instance()->signers()))->toBe([$inside->id]);
});

test('the type narrows the signer list', function (): void {
    modalSigner('Excluded Signer');
    modalSigner('Kept Signer');

    expect(array_values(openModal()->set('form.type', 'permit')->instance()->signers()))->toBe(['Excluded Signer', 'Kept Signer'])
        ->and(array_values(openModal()->set('form.type', 'signed')->instance()->signers()))->toBe(['Kept Signer']);
});

test('a user with a newer profile outside the organization is not a signer', function (): void {
    $user = modalSigner('Moved');
    Profile::factory()->for($user)->create();

    expect(openModal()->set('form.type', 'permit')->instance()->signers())->toBeEmpty();
});

test('manual numbering shows the reference input and override numbering shows the sequence number input', function (): void {
    openModal()->set('form.type', 'permit')
        ->assertSeeHtml('wire:model="form.reference_number"')
        ->assertDontSeeHtml('wire:model="form.sequence_number"')
        ->set('form.type', 'override')
        ->assertSeeHtml('wire:model="form.sequence_number"')
        ->assertDontSeeHtml('wire:model="form.reference_number"');
});

test('computer-signed turns on the letterhead', function (): void {
    openModal()->set('form.type', 'signed')
        ->assertSet('form.with_letterhead', false)
        ->set('form.computer_signed', true)
        ->assertSet('form.with_letterhead', true);
});

test('the letterhead of the printer shows by name, and no match shows no letterhead', function (): void {
    openModal()->set('form.type', 'permit')->assertSee(__('letters/letters.no_letterhead'));

    Letterhead::factory()->for($this->organization)->create(['name' => 'Main Office']);

    openModal()->set('form.type', 'permit')->assertSee('Main Office');
});

test('the letterhead of the signer wins over the letterhead of the printer', function (): void {
    $department = Department::factory()->for($this->organization)->create();
    Letterhead::factory()->for($this->organization)->create(['name' => 'Printer Office']);
    Letterhead::factory()->for($this->organization)->create(['name' => 'Signer Office', 'department_id' => $department->id]);
    $signer = User::factory()->create();
    $position = Position::factory()->for($this->organization)->create(['department_id' => $department->id]);
    Profile::factory()->for($this->organization)->for($signer)->create(['department_id' => $department->id, 'position_id' => $position->id]);

    openModal()->set('form.type', 'permit')->set('form.signer_id', $signer->id)->assertSee('Signer Office');
});

test('saving creates a letter with the signer, the carbon copies, and the extra fields', function (): void {
    $signer = modalSigner();
    $department = Department::factory()->for($this->organization)->create(['name' => 'Finance']);
    $other = Organization::factory()->create(['name' => 'Partner']);

    openModal()
        ->set('form.type', 'extra')
        ->set('form.reference_number', 'REF/1')
        ->set('form.date', '2026-03-04')
        ->set('form.signer_id', $signer->id)
        ->set('form.carbon_copies', [
            ['kind' => 'organization', 'target' => $other->id, 'text' => ''],
            ['kind' => 'department', 'target' => $department->id, 'text' => ''],
            ['kind' => 'text', 'target' => null, 'text' => 'The Registrar'],
        ])
        ->set('form.extra', ['remark' => 'Urgent', '_groups' => ['guarantor' => true], 'guarantor' => ['name' => 'Ali', 'phone' => '012']])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched(LetterModal::event())
        ->assertSet('showing', false);

    $letter = $this->permit->letters()->firstOrFail();

    expect($letter->type)->toBe('extra')
        ->and($letter->reference_number)->toBe('REF/1')
        ->and($letter->date?->toDateString())->toBe('2026-03-04')
        ->and($letter->carbon_copies)->toEqual([
            new CarbonCopy('Partner', 'organization', $other->id),
            new CarbonCopy('Finance', 'department', $department->id),
            new CarbonCopy('The Registrar'),
        ])
        ->and($letter->extra)->toBe(['remark' => 'Urgent', 'guarantor' => ['name' => 'Ali', 'phone' => '012'], 'stamp' => null])
        ->and($letter->signature?->actor?->is($signer))->toBeTrue();
});

test('a group switched on with no field filled is an error on the group', function (): void {
    openModal()
        ->set('form.type', 'extra')
        ->set('form.extra', ['remark' => 'Urgent', '_groups' => ['guarantor' => true], 'guarantor' => ['name' => '', 'phone' => '']])
        ->call('save')
        ->assertHasErrors(['form.extra.guarantor']);

    expect($this->permit->letters()->count())->toBe(0);
});

test('a group switched off is not saved', function (): void {
    openModal()
        ->set('form.type', 'extra')
        ->set('form.extra', ['remark' => 'Urgent', '_groups' => ['guarantor' => false], 'guarantor' => ['name' => 'Ali', 'phone' => '012']])
        ->call('save')
        ->assertHasNoErrors();

    expect($this->permit->letters()->firstOrFail()->extra)->toBe(['remark' => 'Urgent', 'guarantor' => null, 'stamp' => null]);
});

test('an uploaded extra image is stored and an existing one is kept', function (): void {
    $item = Livewire::test(FileUpload::class, ['fileRules' => ['image']])
        ->set('uploads', UploadedFile::fake()->image('stamp.png', 40, 40))
        ->get('items')[0];

    openModal()
        ->set('form.type', 'extra')
        ->set('form.extra', ['remark' => 'Urgent', '_groups' => [], 'stamp' => [$item]])
        ->call('save')
        ->assertHasNoErrors();

    $letter = $this->permit->letters()->firstOrFail();
    $uuid = $letter->extra['stamp'];

    expect($letter->getMedia(LetterMedia::Data->value))->toHaveCount(1)
        ->and($letter->getFirstMedia(LetterMedia::Data->value)?->uuid)->toBe($uuid);

    openModal($letter)
        ->assertSet('form.extra.stamp.0.uuid', $uuid)
        ->set('form.extra.remark', 'Changed')
        ->call('save')
        ->assertHasNoErrors();

    expect($letter->refresh()->extra['stamp'])->toBe($uuid)
        ->and($letter->extra['remark'])->toBe('Changed')
        ->and($letter->getMedia(LetterMedia::Data->value))->toHaveCount(1);
});

test('removing the extra image clears it', function (): void {
    $item = Livewire::test(FileUpload::class, ['fileRules' => ['image']])
        ->set('uploads', UploadedFile::fake()->image('stamp.png', 40, 40))
        ->get('items')[0];

    openModal()->set('form.type', 'extra')->set('form.extra', ['remark' => 'x', 'stamp' => [$item]])->call('save');
    $letter = $this->permit->letters()->firstOrFail();

    openModal($letter)->set('form.extra.stamp', [])->call('save')->assertHasNoErrors();

    expect($letter->refresh()->extra['stamp'])->toBeNull();
});

test('a user with only print can change the signer but not the date', function (): void {
    $signer = modalSigner();
    $letter = savedLetter('signed', ['reference_number' => 'REF/1']);
    $this->permit->update(['locked' => true]);

    $component = openModal($letter);

    expect($component->instance()->editable('signer'))->toBeTrue()
        ->and($component->instance()->editable('date'))->toBeFalse()
        ->and($component->instance()->editable('reference_number'))->toBeFalse();

    $component->set('form.signer_id', $signer->id)
        ->set('form.date', '2027-01-01')
        ->set('form.reference_number', 'OTHER')
        ->call('save')
        ->assertHasNoErrors();

    $letter->refresh();

    expect($letter->date?->toDateString())->toBe('2026-03-04')
        ->and($letter->reference_number)->toBe('REF/1')
        ->and($letter->signature?->actor?->is($signer))->toBeTrue();
});

function letterAbilities(bool $update, bool $print): void
{
    ToggledLetterPolicy::$update = $update;
    ToggledLetterPolicy::$print = $print;
    Gate::policy(Letter::class, ToggledLetterPolicy::class);
}

test('a hidden letter cannot be opened', function (): void {
    $letter = savedLetter('permit');
    $this->permit->update(['hidden' => true]);

    Livewire::test(LetterModal::class, ['source' => $this->permit])->call('show', $letter->id)->assertForbidden();
});

test('a user who can neither print nor update sees every part read-only', function (): void {
    $letter = savedLetter('signed');
    letterAbilities(update: false, print: false);

    $component = openModal($letter);

    expect(collect(['signer', 'carbon_copies', 'date', 'extra', 'reference_number'])->contains(fn (string $part): bool => $component->instance()->editable($part)))->toBeFalse();

    $component->call('save')->assertForbidden();
});

test('a user with only update cannot change the signer', function (): void {
    $existing = modalSigner('Existing');
    $other = modalSigner('Other');
    $letter = savedLetter('signed');
    $letter->act(Act::Sign, $existing);
    letterAbilities(update: true, print: false);

    $component = openModal($letter);

    expect($component->instance()->editable('signer'))->toBeFalse()
        ->and($component->instance()->editable('date'))->toBeTrue();

    $component->set('form.signer_id', $other->id)->set('form.date', '2027-01-01')->call('save')->assertHasNoErrors();

    expect($letter->refresh()->date?->toDateString())->toBe('2027-01-01')
        ->and($letter->signature?->actor?->is($existing))->toBeTrue();
});

test('a user with only update saves a letter whose signer moved to another organization', function (): void {
    $signer = modalSigner('Mover');
    $letter = savedLetter('signed');
    $letter->act(Act::Sign, $signer);
    modalSigner('Filler');
    $elsewhere = Organization::factory()->create();
    Profile::factory()->for($elsewhere)->for($signer)->create(['position_id' => Position::factory()->for($elsewhere)->create()->id]);
    letterAbilities(update: true, print: false);

    openModal($letter)->set('form.date', '2027-02-02')->call('save')->assertHasNoErrors();

    expect($letter->refresh()->date?->toDateString())->toBe('2027-02-02')
        ->and($letter->signature?->actor?->is($signer))->toBeTrue();
});

test('a user with only update saves a letter whose signer lost the position', function (): void {
    $signer = modalSigner('Demoted');
    $letter = savedLetter('signed');
    $letter->act(Act::Sign, $signer);
    Profile::factory()->for($this->organization)->for($signer)->create();
    letterAbilities(update: true, print: false);

    openModal($letter)->set('form.date', '2027-03-03')->call('save')->assertHasNoErrors();

    expect($letter->refresh()->date?->toDateString())->toBe('2027-03-03')
        ->and($letter->signature?->actor?->is($signer))->toBeTrue();
});

test('a user with only update saves a letter whose carbon copy department was deleted', function (): void {
    $department = Department::factory()->for($this->organization)->create(['name' => 'Gone']);
    $letter = savedLetter('permit', ['carbon_copies' => [new CarbonCopy('Gone', 'department', $department->id)]]);
    $department->delete();
    letterAbilities(update: true, print: false);

    openModal($letter)->set('form.date', '2027-02-02')->call('save')->assertHasNoErrors();

    expect($letter->refresh()->date?->toDateString())->toBe('2027-02-02')
        ->and($letter->carbon_copies)->toEqual([new CarbonCopy('Gone', 'department', $department->id)]);
});

test('a type whose create ability the user lacks is not listed and cannot be saved', function (): void {
    expect(openModal()->instance()->types())->not->toHaveKey('strict');

    openModal()->set('form.type', 'strict')->call('save')->assertHasErrors(['form.type']);

    $this->permit->update(['name' => 'Archivable']);

    expect(openModal()->instance()->types())->toHaveKey('strict');
});

test('on a new letter the print parts follow the print ability of the chosen type', function (): void {
    $signer = modalSigner();

    $component = openModal()->set('form.type', 'guarded');

    expect($component->instance()->editable('signer'))->toBeFalse()
        ->and($component->instance()->editable('carbon_copies'))->toBeFalse()
        ->and($component->instance()->editable('computer_signed'))->toBeFalse()
        ->and($component->instance()->editable('date'))->toBeTrue();

    $component->set('form.signer_id', $signer->id)->set('form.computer_signed', true)->call('save')->assertHasNoErrors();

    $letter = $this->permit->letters()->firstOrFail();

    expect($letter->signature)->toBeNull()
        ->and($letter->computer_signed)->toBeFalse();

    $this->permit->update(['name' => 'Archivable']);

    expect(openModal()->set('form.type', 'guarded')->instance()->editable('signer'))->toBeTrue();
});

test('a new letter hides a type that locks a required extra field', function (): void {
    expect(openModal()->instance()->types())->not->toHaveKey('locked');

    $letter = savedLetter('locked', ['extra' => ['remark' => 'Kept', 'guarantor' => null, 'stamp' => null]]);

    openModal($letter)->assertSet('form.type', 'locked')->call('save')->assertHasNoErrors();
});

test('a crafted carbon copy row is a validation error', function (): void {
    openModal()->set('form.type', 'permit')->set('form.carbon_copies', ['x'])->call('save')->assertHasErrors(['form.carbon_copies.0']);
    openModal()->set('form.type', 'permit')->set('form.carbon_copies', [['text' => 'x']])->call('save')->assertHasErrors(['form.carbon_copies.0.kind']);
});

test('the letterhead of a signer outside the list is not used', function (): void {
    $other = Organization::factory()->create();
    Letterhead::factory()->for($other)->create(['name' => 'Foreign Office']);
    $outside = modalSigner('Outside', $other);

    openModal()->set('form.type', 'permit')->set('form.signer_id', $outside->id)->assertDontSee('Foreign Office')->assertSee(__('letters/letters.no_letterhead'));
});

test('a letterhead set by the type shows a label, not a view path', function (): void {
    PermitViewHeadLetter::$letterheadView = new LetterheadView('tests::letters.letterhead-office', 20);

    openModal()->set('form.type', 'viewhead')->assertSee(__('letters/letters.type_letterhead'))->assertDontSee('tests::letters.letterhead-office');
});

test('an error in a table row names the row', function (): void {
    openModal()
        ->set('form.type', 'table')
        ->set('form.extra', ['lines' => [
            ['_groups' => ['detail' => false], 'detail' => ['note' => '']],
            ['_groups' => ['detail' => true], 'detail' => ['note' => '']],
        ]])
        ->call('save')
        ->assertHasErrors(['form.extra.lines.1.detail']);
});

test('a locked field keeps its saved value', function (): void {
    $letter = savedLetter('locked', ['extra' => ['remark' => 'Kept', 'guarantor' => null, 'stamp' => null]]);

    $component = openModal($letter);

    expect($component->instance()->editable('date'))->toBeFalse()
        ->and($component->instance()->editable('extra'))->toBeTrue();

    $component->set('form.date', '2027-01-01')
        ->set('form.extra.remark', 'Changed')
        ->call('save')
        ->assertHasNoErrors();

    expect($letter->refresh()->date?->toDateString())->toBe('2026-03-04')
        ->and($letter->extra['remark'])->toBe('Kept');
});

test('a type that offers neither choice saves both as off', function (): void {
    openModal()->set('form.type', 'plain')->set('form.with_letterhead', true)->set('form.computer_signed', true)->call('save');

    $letter = $this->permit->letters()->firstOrFail();

    expect($letter->with_letterhead)->toBeFalse()
        ->and($letter->computer_signed)->toBeFalse();
});

test('a computer-signed letter is saved with the letterhead', function (): void {
    $signer = modalSigner();

    openModal()->set('form.type', 'signed')->set('form.signer_id', $signer->id)->set('form.computer_signed', true)->call('save')->assertHasNoErrors();

    $letter = $this->permit->letters()->firstOrFail();

    expect($letter->computer_signed)->toBeTrue()
        ->and($letter->with_letterhead)->toBeTrue();
});

test('a required signer must be chosen', function (): void {
    openModal()->set('form.type', 'signed')->call('save')->assertHasErrors(['form.signer_id']);
});

test('a signer outside the list is refused', function (): void {
    $outside = modalSigner('Outside', Organization::factory()->create());

    openModal()->set('form.type', 'signed')->set('form.signer_id', $outside->id)->call('save')->assertHasErrors(['form.signer_id']);
});

test('a type that is not allowed is refused', function (): void {
    openModal()->set('form.type', 'restricted')->call('save')->assertHasErrors(['form.type']);

    expect($this->permit->letters()->count())->toBe(0);
});

test('an override letter takes the typed sequence number', function (): void {
    openModal()->set('form.type', 'override')->set('form.sequence_number', 7)->call('save')->assertHasNoErrors();

    $letter = $this->permit->letters()->firstOrFail();

    expect($letter->sequence_number)->toBe(7);

    openModal($letter)->assertSet('form.sequence_number', 7);
});

test('a taken override number is an error on the sequence number', function (): void {
    savedLetter('override', ['sequence_key' => "override:{$this->organization->id}", 'reference_number' => '7']);

    openModal()->set('form.type', 'override')->set('form.sequence_number', 7)->call('save')->assertHasErrors(['form.sequence_number']);
});

test('an override number without an organization is an error on the sequence number', function (): void {
    $this->actingAs(User::factory()->create());

    openModal()->set('form.type', 'override')->set('form.sequence_number', 7)->call('save')->assertHasErrors(['form.sequence_number']);
});

test('editing a letter fills the form from it', function (): void {
    $department = Department::factory()->for($this->organization)->create(['name' => 'Finance']);
    $letter = savedLetter('permit', [
        'reference_number' => 'REF/9',
        'carbon_copies' => [new CarbonCopy('Finance', 'department', $department->id), new CarbonCopy('The Registrar')],
    ]);

    openModal($letter)
        ->assertSet('form.type', 'permit')
        ->assertSet('form.reference_number', 'REF/9')
        ->assertSet('form.carbon_copies', [
            ['kind' => 'department', 'target' => $department->id, 'text' => ''],
            ['kind' => 'text', 'target' => null, 'text' => 'The Registrar'],
        ]);
});

test('the type of a saved letter cannot change', function (): void {
    $letter = savedLetter('permit');

    openModal($letter)->set('form.type', 'plain')->set('form.date', '2027-01-01')->call('save')->assertHasNoErrors();

    expect($letter->refresh()->type)->toBe('permit')
        ->and($letter->date?->toDateString())->toBe('2027-01-01');
});

test('adding a letter needs create on the source', function (): void {
    $this->permit->update(['locked' => true]);

    Livewire::test(LetterModal::class, ['source' => $this->permit])->call('show')->assertForbidden();
});

test('the modal ignores a show event for another source', function (): void {
    $other = Permit::query()->create(['name' => 'Other']);

    Livewire::test(LetterModal::class, ['source' => test()->permit])
        ->call('show', null, LetterModal::scope($other))
        ->assertSet('showing', false)
        ->call('show', null, LetterModal::scope(test()->permit))
        ->assertSet('showing', true);
});

test('the modal tells only the table of its own source that it saved', function (): void {
    $other = Permit::query()->create(['name' => 'Other']);

    openModal()
        ->set('form.type', 'plain')
        ->call('save')
        ->assertDispatched(LetterModal::scopedEvent(test()->permit))
        ->assertNotDispatched(LetterModal::scopedEvent($other));
});
