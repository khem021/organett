<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The FarmScope global scope appends `WHERE farm_id = ?` to every tenant query,
 * so farm_id needs to be indexed everywhere. Composite indexes cover the hot
 * "filter within my farm" list screens.
 */
return new class extends Migration
{
    /** table => list of index column-sets */
    private array $indexes = [
        'users' => [['farm_id']],
        'production_batches' => [['farm_id'], ['farm_id', 'status']],
        'harvest_records' => [['farm_id'], ['farm_id', 'harvest_date']],
        'inventory' => [['farm_id']],
        'inventory_transactions' => [['farm_id']],
        'customers' => [['farm_id']],
        'orders' => [['farm_id'], ['farm_id', 'order_status'], ['farm_id', 'payment_status']],
        'deliveries' => [['farm_id']],
        'sales' => [['farm_id'], ['farm_id', 'sale_date']],
        'activity_logs' => [['farm_id'], ['farm_id', 'created_at'], ['farm_id', 'module']],
        'alerts' => [['farm_id'], ['farm_id', 'is_resolved']],
        'settings' => [['farm_id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $sets) {
            Schema::table($table, function (Blueprint $t) use ($sets) {
                foreach ($sets as $columns) {
                    $t->index($columns);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $sets) {
            Schema::table($table, function (Blueprint $t) use ($sets) {
                foreach ($sets as $columns) {
                    $t->dropIndex($columns);
                }
            });
        }
    }
};
