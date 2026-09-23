<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repairs data that pre-dates the multi-tenant work:
 *  - normalises the legacy role vocabulary (admin/staff -> farm_admin/farm_staff)
 *  - stamps a farm_id on every tenant row that is still NULL
 *
 * Uses the query builder (not Eloquent) so global scopes / creating hooks
 * never interfere while the migration runs.
 */
return new class extends Migration
{
    private array $tenantTables = [
        'production_batches', 'harvest_records', 'inventory', 'inventory_transactions',
        'customers', 'orders', 'deliveries', 'sales', 'activity_logs', 'alerts', 'settings',
    ];

    public function up(): void
    {
        // 1. Normalise legacy roles.
        DB::table('users')->where('role', 'admin')->update(['role' => 'farm_admin']);
        DB::table('users')->where('role', 'staff')->update(['role' => 'farm_staff']);

        // Drop the retired "deliveries" feature flag (it gated nothing).
        DB::table('farm_features')->where('feature_key', 'deliveries')->delete();

        // 2. Find the farm everything should belong to (oldest farm on record).
        $farmId = DB::table('farms')->orderBy('id')->value('id');

        if (! $farmId) {
            // Fresh database with no farms yet — seeder will populate a consistent state.
            return;
        }

        // 3. Assign orphaned non-super-admin users to that farm.
        DB::table('users')
            ->whereNull('farm_id')
            ->where('role', '!=', 'super_admin')
            ->update(['farm_id' => $farmId]);

        // 4. Backfill every tenant table.
        foreach ($this->tenantTables as $table) {
            DB::table($table)->whereNull('farm_id')->update(['farm_id' => $farmId]);
        }
    }

    public function down(): void
    {
        // Non-reversible data repair; nothing to roll back.
    }
};
