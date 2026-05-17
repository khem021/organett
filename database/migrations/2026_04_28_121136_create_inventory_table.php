<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->string('item_name', 150);
            $table->string('category', 120);
            $table->string('unit', 50);
            $table->decimal('stock_qty', 10, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->default(0);
            $table->string('location', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};