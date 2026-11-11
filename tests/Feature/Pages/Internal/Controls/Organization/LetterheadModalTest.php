<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Media\Enums\MediaCollection;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\Components\FileUpload;
use App\Livewire\Pages\Internal\Controls\Organization\LetterheadModal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('media');
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.organizations.view', ...collect(['view-any', 'view', 'create', 'update', 'delete'])
        ->map(fn (string $permission): string => "internal.controls.letterheads.$permission")
        ->all());
    $this->actingAs($this->actor);
    $this->organization = Organization::factory()->create();
});

function modalUpload(string $name, int $width, int $height = 300): array
{
    return Livewire::test(FileUpload::class, ['fileRules' => ['image']])
        ->set('uploads', UploadedFile::fake()->image($name, $width, $height))
        ->get('items')[0];
}

test('adding a letterhead needs letterhead create', function (): void {
    revokePermissions($this->actor, 'internal.controls.letterheads.create');

    Livewire::test(LetterheadModal::class, ['organization' => $this->organization])
        ->call('show')
        ->assertForbidden();
});

test('saving adds a letterhead with a header to the organization', function (): void {
    $branch = Branch::factory()->for($this->organization)->create();
    $department = Department::factory()->for($this->organization)->create();

    Livewire::test(LetterheadModal::class, ['organization' => $this->organization])
        ->call('show')
        ->set('form.name', 'Main')
        ->set('form.branch_id', $branch->id)
        ->set('form.department_id', $department->id)
        ->set('form.header', [modalUpload('header.png', 2480)])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched(LetterheadModal::event())
        ->assertSet('showing', false);

    $letterhead = Letterhead::query()->where('name', 'Main')->firstOrFail();

    expect($letterhead->organization_id)->toBe($this->organization->id)
        ->and($letterhead->branch_id)->toBe($branch->id)
        ->and($letterhead->department_id)->toBe($department->id)
        ->and($letterhead->getMedia(MediaCollection::Header))->toHaveCount(1)
        ->and($letterhead->getMedia(MediaCollection::Footer))->toBeEmpty();
});

test('the header is required and the footer is not', function (): void {
    Livewire::test(LetterheadModal::class, ['organization' => $this->organization])
        ->call('show')
        ->set('form.name', 'Main')
        ->call('save')
        ->assertHasErrors(['form.header' => 'required']);
});

test('an image narrower than 2480 pixels is refused', function (): void {
    Livewire::test(LetterheadModal::class, ['organization' => $this->organization])
        ->call('show')
        ->set('form.name', 'Main')
        ->set('form.header', [modalUpload('header.png', 2479)])
        ->call('save')
        ->assertHasErrors('form.header');

    expect(Letterhead::query()->count())->toBe(0);
});

test('an image larger than 10 MB is refused', function (): void {
    $image = UploadedFile::fake()->image('header.png', 2480, 300);
    file_put_contents($image->getRealPath(), str_repeat("\0", 10 * 1024 * 1024), FILE_APPEND);
    $item = Livewire::test(FileUpload::class, ['fileRules' => ['image']])->set('uploads', $image)->get('items')[0];

    Livewire::test(LetterheadModal::class, ['organization' => $this->organization])
        ->call('show')
        ->set('form.name', 'Main')
        ->set('form.header', [$item])
        ->call('save')
        ->assertHasErrors('form.header');
});

test('the footer is checked like the header', function (): void {
    Livewire::test(LetterheadModal::class, ['organization' => $this->organization])
        ->call('show')
        ->set('form.name', 'Main')
        ->set('form.header', [modalUpload('header.png', 2480)])
        ->set('form.footer', [modalUpload('footer.png', 2479)])
        ->call('save')
        ->assertHasErrors('form.footer');
});

test('a branch or department of another organization is refused', function (): void {
    Livewire::test(LetterheadModal::class, ['organization' => $this->organization])
        ->call('show')
        ->set('form.name', 'Main')
        ->set('form.branch_id', Branch::factory()->create()->id)
        ->set('form.department_id', Department::factory()->create()->id)
        ->set('form.header', [modalUpload('header.png', 2480)])
        ->call('save')
        ->assertHasErrors(['form.branch_id' => 'exists', 'form.department_id' => 'exists']);
});

test('the selects list only branches and departments of this organization', function (): void {
    $branch = Branch::factory()->for($this->organization)->create();
    $department = Department::factory()->for($this->organization)->create();
    Branch::factory()->create();
    Department::factory()->create();

    $modal = Livewire::test(LetterheadModal::class, ['organization' => $this->organization])->instance();

    expect(array_keys($modal->branches()))->toBe([$branch->id])
        ->and(array_keys($modal->departments()))->toBe([$department->id]);
});

test('a second letterhead for the same placement is refused', function (): void {
    Letterhead::factory()->for($this->organization)->create();

    Livewire::test(LetterheadModal::class, ['organization' => $this->organization])
        ->call('show')
        ->set('form.name', 'Second')
        ->set('form.header', [modalUpload('header.png', 2480)])
        ->call('save')
        ->assertHasErrors('placement');
});

test('editing a letterhead keeps its images when none are changed', function (): void {
    $letterhead = Letterhead::factory()->for($this->organization)->withImages()->create(['name' => 'Old']);

    Livewire::test(LetterheadModal::class, ['organization' => $this->organization])
        ->call('show', $letterhead->id)
        ->assertSet('form.name', 'Old')
        ->set('form.name', 'New')
        ->call('save')
        ->assertHasNoErrors();

    expect($letterhead->fresh())
        ->name->toBe('New')
        ->and($letterhead->getMedia(MediaCollection::Header))->toHaveCount(1)
        ->and($letterhead->getMedia(MediaCollection::Footer))->toHaveCount(1);
});

test('a letterhead of another organization is not found', function (): void {
    Livewire::test(LetterheadModal::class, ['organization' => $this->organization])
        ->call('show', Letterhead::factory()->create()->id)
        ->assertNotFound();
});

test('without letterhead update the modal is read only', function (): void {
    revokePermissions($this->actor, 'internal.controls.letterheads.update');
    $letterhead = Letterhead::factory()->for($this->organization)->withImages()->create();

    Livewire::test(LetterheadModal::class, ['organization' => $this->organization])
        ->call('show', $letterhead->id)
        ->call('save')
        ->assertForbidden();
});
