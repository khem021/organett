<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two tenant tables kept a global unique index from before multi-tenancy, the
 * same oversight already corrected for orders.order_no:
 *
 *  - settings.setting_key: Setting::updateOrCreate(['setting_key' => ...]) looks
 *    up through the farm scope, finds nothing for a second farm, then inserts —
 *    colliding with the first farm's row. Saving Settings was a hard 500 for
 *    every farm but the one that saved first, which also left their printed
 *    receipts falling back to config('app.name').
 *
 *  - production_batches.batch_code: a farm could not reuse a batch code another
 *    farm had taken, failing validation against a record it cannot see.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_setting_key_unique');
            $table->unique(['farm_id', 'setting_key']);
        });

        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropUnique('production_batches_batch_code_unique');
            $table->unique(['farm_id', 'batch_code']);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['farm_id', 'setting_key']);
            $table->unique('setting_key');
        });

        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropUnique(['farm_id', 'batch_code']);
            $table->unique('batch_code');
        });
    }
};
