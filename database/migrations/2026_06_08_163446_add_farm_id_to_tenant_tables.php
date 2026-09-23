<?php

use App\Models\Farm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    private array $tables = [
        'users',
        'production_batches',
        'harvest_records',
        'inventory',
        'inventory_transactions',
        'customers',
        'orders',
        'deliveries',
        'sales',
        'activity_logs',
        'alerts',
        'settings',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('farm_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeignIdFor(Farm::class);
                $blueprint->dropColumn('farm_id');
            });
        }
    }
};
