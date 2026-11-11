<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letterheads', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Organization::class)->index()->constrained(new Organization()->getTable());
            $table->foreignIdFor(Branch::class)->nullable()->index()->constrained(new Branch()->getTable());
            $table->foreignIdFor(Department::class)->nullable()->index()->constrained(new Department()->getTable());
            $table->string('name');
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement('create unique index letterheads_placement_unique on letterheads (organization_id, coalesce(branch_id, 0), coalesce(department_id, 0)) where deleted_at is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('letterheads');
    }
};
