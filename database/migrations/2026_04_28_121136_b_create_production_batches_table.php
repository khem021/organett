<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 100)->unique();
            $table->string('substrate_type', 150);
            $table->string('spawn_type', 150);
            $table->date('inoculation_date');
            $table->date('expected_harvest_date');
            $table->enum('status', ['planned', 'inoculated', 'fruiting', 'harvested', 'completed', 'contaminated'])->default('planned');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};