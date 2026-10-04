<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            // Archiving rather than deleting: every tenant table points at farms
            // with nullOnDelete, so a real delete would orphan the rows instead
            // of removing them.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
