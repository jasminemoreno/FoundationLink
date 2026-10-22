<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * In-app promotions of a running campaign: shown first to donors with a
 * "Promoted" label between starts_at and ends_at, and announced to followers.
 * Kept after they end, so the Reports timeline can show them on the chart.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('message', 200);
            $table->unsignedTinyInteger('duration_days'); // 3, 7 or 14
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('notified_count')->default(0); // followers told
            $table->timestamps();

            $table->index(['campaign_id', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_promotions');
    }
};
