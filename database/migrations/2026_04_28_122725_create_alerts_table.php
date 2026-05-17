<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_type', 120);
            $table->text('message');
            $table->enum('severity', ['warning', 'critical', 'info'])->default('warning');
            $table->string('source_module', 100);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};