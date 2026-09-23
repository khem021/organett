<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('customer_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('order_id');
            $table->index('customer_id');
        });

        Schema::table('harvest_records', function (Blueprint $table) {
            $table->index('batch_id');
        });

        Schema::table('production_batches', function (Blueprint $table) {
            $table->index('expected_harvest_date');
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->index('inventory_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['customer_id']);
        });

        Schema::table('harvest_records', function (Blueprint $table) {
            $table->dropIndex(['batch_id']);
        });

        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropIndex(['expected_harvest_date']);
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex(['inventory_id']);
            $table->dropIndex(['created_by']);
        });
    }
};
