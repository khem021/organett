<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('order_date');
            $table->index('delivery_date');
            $table->index('order_status');
            $table->index('payment_status');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('sale_date');
        });

        Schema::table('harvest_records', function (Blueprint $table) {
            $table->index('harvest_date');
            $table->index('quality_grade');
        });

        Schema::table('production_batches', function (Blueprint $table) {
            $table->index('status');
            $table->index('inoculation_date');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->index('stock_qty');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['order_date']);
            $table->dropIndex(['delivery_date']);
            $table->dropIndex(['order_status']);
            $table->dropIndex(['payment_status']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['sale_date']);
        });

        Schema::table('harvest_records', function (Blueprint $table) {
            $table->dropIndex(['harvest_date']);
            $table->dropIndex(['quality_grade']);
        });

        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['inoculation_date']);
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropIndex(['stock_qty']);
            $table->dropIndex(['category']);
        });
    }
};
