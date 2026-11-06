<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\PostOffice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config()->string('address.tables.postcodes'), function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(PostOffice::class)->index()->constrained(new PostOffice()->getTable())->cascadeOnDelete();
            $table->string('code')->index();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['post_office_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config()->string('address.tables.postcodes'));
    }
};
