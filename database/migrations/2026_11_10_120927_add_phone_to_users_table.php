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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone')->nullable()->after('email_verified_at');
            $table->timestampTz('phone_verified_at')->nullable()->after('phone');
        });

        DB::statement('create unique index users_phone_unique on users (phone) where deleted_at is null');
    }

    public function down(): void
    {
        DB::statement('drop index users_phone_unique');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['phone', 'phone_verified_at']);
        });
    }
};
