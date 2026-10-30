<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exact pick-up spot a donor may share (phone GPS) besides the typed address,
     * so the foundation can get directions. Only used for Pick Up item donations.
     */
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->decimal('pickup_latitude', 10, 7)->nullable()->after('delivery_address');
            $table->decimal('pickup_longitude', 10, 7)->nullable()->after('pickup_latitude');
            // how close the reading is, in metres (phones ~5–20 m, laptops can be far off)
            $table->unsignedInteger('pickup_accuracy')->nullable()->after('pickup_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['pickup_latitude', 'pickup_longitude', 'pickup_accuracy']);
        });
    }
};
