<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\District;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config()->string('address.tables.subdistricts'), function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(District::class)->index()->constrained(new District()->getTable())->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['district_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config()->string('address.tables.subdistricts'));
    }
};
