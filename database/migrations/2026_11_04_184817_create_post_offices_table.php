<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\State;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config()->string('address.tables.post_offices'), function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(State::class)->index()->constrained(new State()->getTable())->cascadeOnDelete();
            $table->string('name');
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['state_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config()->string('address.tables.post_offices'));
    }
};
