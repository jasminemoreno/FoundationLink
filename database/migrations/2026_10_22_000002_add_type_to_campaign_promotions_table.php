<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promotion type: urgent need / deadline / milestone (shown on the Reports
 * chart so foundations can compare which kind works best). Promotions made
 * before types existed count as "urgent".
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaign_promotions', function (Blueprint $table) {
            $table->string('type', 20)->default('urgent')->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_promotions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
