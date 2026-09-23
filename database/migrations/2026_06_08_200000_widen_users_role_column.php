<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The original users.role enum only allowed admin/staff. The multi-tenant roles
 * (super_admin, farm_admin, farm_staff) are written by the backfill migration that
 * follows, so the column must accept them first. SQLite already lost the CHECK
 * constraint when the table was rebuilt; PostgreSQL and MySQL still enforce it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('farm_staff')->change();
        });
    }

    public function down(): void
    {
        // Rows may already hold the new role names; narrowing back would fail.
    }
};
