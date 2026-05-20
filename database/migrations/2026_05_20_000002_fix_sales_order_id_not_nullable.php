<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove any orphan sales rows (order_id IS NULL) before enforcing NOT NULL
        DB::table('sales')->whereNull('order_id')->delete();

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable(false)->change();
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
        });
    }
};
