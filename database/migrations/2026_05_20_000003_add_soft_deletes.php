<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('harvest_records', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('harvest_records', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
