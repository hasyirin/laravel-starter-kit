<?php

declare(strict_types=1);

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
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10);
            $table->string('name');
            $table->boolean('is_owner')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Organization::class)->index()->constrained(new Organization()->getTable());
            $table->string('code', 10);
            $table->string('name');
            $table->boolean('is_owner')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Organization::class)->index()->constrained(new Organization()->getTable());
            $table->string('code', 10);
            $table->string('name');
            $table->boolean('is_owner')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Organization::class)->index()->constrained(new Organization()->getTable());
            $table->foreignIdFor(Department::class)->nullable()->index()->constrained(new Department()->getTable())->nullOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('create unique index organizations_code_unique on organizations (code) where deleted_at is null');
        DB::statement('create unique index departments_organization_id_code_unique on departments (organization_id, code) where deleted_at is null');
        DB::statement('create unique index branches_organization_id_code_unique on branches (organization_id, code) where deleted_at is null');

        DB::statement('create unique index organizations_is_owner_unique on organizations (is_owner) where is_owner = true and deleted_at is null');
        DB::statement('create unique index departments_is_owner_unique on departments (is_owner) where is_owner = true and deleted_at is null');
        DB::statement('create unique index branches_is_owner_unique on branches (is_owner) where is_owner = true and deleted_at is null');
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('organizations');
    }
};
