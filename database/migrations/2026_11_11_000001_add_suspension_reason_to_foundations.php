<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Why the super admin suspended a foundation, shown to its admin on the dashboard.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('foundations', function (Blueprint $table) {
            $table->text('suspension_reason')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('foundations', function (Blueprint $table) {
            $table->dropColumn('suspension_reason');
        });
    }
};
