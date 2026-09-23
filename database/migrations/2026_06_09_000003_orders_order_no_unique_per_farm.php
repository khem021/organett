<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * order_no is generated per-farm ("ORD-2026-001" restarts for each farm), so the
 * uniqueness constraint has to be scoped to the farm too — otherwise the second
 * farm to create an order hits a global unique-constraint violation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_order_no_unique');
            $table->unique(['farm_id', 'order_no']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['farm_id', 'order_no']);
            $table->unique('order_no');
        });
    }
};
