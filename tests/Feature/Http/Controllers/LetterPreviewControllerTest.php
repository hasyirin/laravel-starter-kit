<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Spatie\LaravelPdf\Facades\Pdf;
use Tests\Fixtures\Support\PrintedNoticeLetter;
use Tests\Fixtures\Support\RestrictedNoticeLetter;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    Pdf::fake();
    config(['system.letters.types' => ['printed' => PrintedNoticeLetter::class]]);
    $this->letter = Letter::factory()->ofType('printed')->create(['extra' => ['remark' => 'Reminder']]);
    $this->user = User::factory()->create();
});

test('a guest cannot preview', function (): void {
    $this->get(route('letters.preview', $this->letter))->assertRedirect();
});

test('a user who cannot print the letter gets a 403', function (): void {
    $this->actingAs($this->user)->get(route('letters.preview', $this->letter))->assertForbidden();
});

test('a user who can view the source previews the letter as a pdf', function (): void {
    grantPermissions($this->user, 'internal.controls.organizations.view');

    $response = $this->actingAs($this->user)->get(route('letters.preview', $this->letter));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('inline');
    Pdf::assertSee('Draft');
    expect($this->letter->fresh()?->reference_number)->toBeNull();
});

test('a user who can view the source gets a 403 for a letter type that is not allowed for the user', function (): void {
    config(['system.letters.types' => ['restricted' => RestrictedNoticeLetter::class]]);
    grantPermissions($this->user, 'internal.controls.organizations.view');
    $letter = Letter::factory()->ofType('restricted')->create();

    $this->actingAs($this->user)->get(route('letters.preview', $letter))->assertForbidden();
});

test('a missing letter gets a 404', function (): void {
    $this->actingAs($this->user)->get(route('letters.preview', 0))->assertNotFound();
});
