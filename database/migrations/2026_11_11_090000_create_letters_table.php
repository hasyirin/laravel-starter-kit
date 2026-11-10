<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letters', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->morphs('source');
            $table->string('reference_number')->nullable();
            $table->string('sequence_key')->nullable();
            $table->unsignedBigInteger('sequence_number')->nullable();
            $table->unsignedBigInteger('sequence_cycle')->nullable();
            $table->date('date')->nullable();
            $table->json('carbon_copies');
            $table->json('extra');
            $table->boolean('with_letterhead');
            $table->boolean('computer_signed');
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement("create unique index letters_sequence_reference_unique on letters (coalesce(sequence_key, ''), reference_number) where deleted_at is null and reference_number is not null");
    }

    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
