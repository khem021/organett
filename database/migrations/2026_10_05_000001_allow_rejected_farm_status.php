<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * farms.status was created as an enum of pending / active / inactive, so a farm the
 * platform admin turned down could only be stored as "inactive" — indistinguishable
 * from one that had been suspended. The login screen needs to tell them apart.
 *
 * PostgreSQL enforces an enum with a CHECK constraint named after the column, which
 * has to go before the column can hold a new value; changing the column to a plain
 * string rebuilds the table on SQLite and alters it in place elsewhere. The valid
 * values are enforced by the controllers, as they already are for users.role.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE farms DROP CONSTRAINT IF EXISTS farms_status_check');
        }

        Schema::table('farms', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->change();
        });
    }

    public function down(): void
    {
        // Rows may already hold "rejected"; narrowing the column back would fail.
    }
};
