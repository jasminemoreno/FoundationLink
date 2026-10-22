<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FoundationController::suspend() writes status = 'suspended', but the original
 * enum did not include it, so MySQL strict mode rejected every suspension.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('foundations', function (Blueprint $table) {
            $table->enum('status', [
                'incomplete',
                'pending_verification',
                'under_review',
                'verified',
                'rejected',
                'suspended',
            ])->default('pending_verification')->change();
        });
    }

    public function down(): void
    {
        Schema::table('foundations', function (Blueprint $table) {
            $table->enum('status', [
                'incomplete',
                'pending_verification',
                'under_review',
                'verified',
                'rejected',
            ])->default('pending_verification')->change();
        });
    }
};
