<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config()->string('address.tables.addresses'), function (Blueprint $table): void {
            $table->id();
            $table->morphs('addressable');
            $table->foreignIdFor(Country::class)->nullable()->index()->constrained(new Country()->getTable())->nullOnDelete();
            $table->foreignIdFor(State::class)->nullable()->index()->constrained(new State()->getTable())->nullOnDelete();
            $table->foreignIdFor(District::class)->nullable()->index()->constrained(new District()->getTable())->nullOnDelete();
            $table->foreignIdFor(Subdistrict::class)->nullable()->index()->constrained(new Subdistrict()->getTable())->nullOnDelete();
            $table->foreignIdFor(PostOffice::class)->nullable()->index()->constrained(new PostOffice()->getTable())->nullOnDelete();
            $table->string('type');
            $table->string('line_1')->nullable();
            $table->string('line_2')->nullable();
            $table->string('line_3')->nullable();
            $table->string('postcode')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->json('properties');
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config()->string('address.tables.addresses'));
    }
};
