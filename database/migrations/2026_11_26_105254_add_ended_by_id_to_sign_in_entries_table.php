<?php

declare(strict_types=1);

use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(new SignInEntry()->getTable(), function (Blueprint $table): void {
            $table->foreignId('ended_by_id')->nullable()->after('impersonator_id')->index()->constrained(new User()->getTable())->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table(new SignInEntry()->getTable(), function (Blueprint $table): void {
            $table->dropForeign(['ended_by_id']);
            $table->dropIndex(['ended_by_id']);
            $table->dropColumn('ended_by_id');
        });
    }
};
