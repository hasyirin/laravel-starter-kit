<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Organization::class)->nullable()->index()->constrained(new Organization()->getTable());
            $table->foreignIdFor(Department::class)->nullable()->index()->constrained(new Department()->getTable());
            $table->foreignIdFor(Position::class)->nullable()->index()->constrained(new Position()->getTable());
            $table->foreignIdFor(Branch::class)->nullable()->index()->constrained(new Branch()->getTable());
            $table->foreignIdFor(User::class)->index()->constrained(new User()->getTable())->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
