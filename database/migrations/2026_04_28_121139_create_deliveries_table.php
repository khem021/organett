<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->text('destination');
            $table->date('delivery_date');
            $table->enum('transport_status', ['scheduled', 'in_transit', 'delivered', 'cancelled'])->default('scheduled');
            $table->string('assigned_personnel', 150)->nullable();
            $table->string('vehicle_info', 150)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};