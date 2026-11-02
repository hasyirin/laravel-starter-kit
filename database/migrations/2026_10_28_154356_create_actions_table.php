<?php

declare(strict_types=1);

use App\Domain\User\Models\Profile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config()->string('actor.tables.actions'), function (Blueprint $table): void {
            $table->id();
            $table->morphs('resource');
            $table->morphs('actor');
            $table->foreignIdFor(Profile::class)->nullable()->index()->constrained(new Profile()->getTable())->nullOnDelete();
            $table->string('name');
            $table->timestampTz('acted_at')->useCurrent();
            $table->timestampsTz();
            $table->unique(['resource_type', 'resource_id', 'name'], 'actions_resource_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config()->string('actor.tables.actions'));
    }
};
