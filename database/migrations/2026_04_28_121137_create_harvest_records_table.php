<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvest_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('production_batches')->restrictOnDelete();
            $table->date('harvest_date');
            $table->decimal('quantity_kg', 10, 2);
            $table->enum('quality_grade', ['A', 'B', 'C'])->default('A');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_records');
    }
};