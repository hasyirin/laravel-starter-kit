<?php

declare(strict_types=1);

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\User\Models\Profile;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Models\Invoice;

beforeEach(function (): void {
    Schema::create('invoices', function (Blueprint $table): void {
        $table->id();
        $table->string('number');
        $table->timestampsTz();
        $table->softDeletesTz();
    });

    Relation::morphMap(['invoice' => Invoice::class]);
});

test('a model that a signed-in user creates records the user as its preparer', function (): void {
    $profile = Profile::factory()->create();
    $this->actingAs($profile->user);

    $invoice = Invoice::query()->create(['number' => 'INV-1']);

    expect($invoice->preparer?->name)->toBe(Act::Prepare)
        ->and($invoice->preparer?->actor_id)->toBe($profile->user_id)
        ->and($invoice->preparer?->profile_id)->toBe($profile->id);
});

test('a model created without a signed-in user has no preparer', function (): void {
    expect(Invoice::query()->create(['number' => 'INV-1'])->preparer)->toBeNull();
});
