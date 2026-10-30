<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pick-up schedule for Pick Up item donations: the day the foundation will come
     * (morning / afternoon / exact time, Manila wall-clock), the donor's answer
     * (confirmed, or another day they asked for).
     */
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->date('pickup_date')->nullable()->after('pickup_accuracy');
            $table->string('pickup_window', 12)->nullable()->after('pickup_date'); // morning | afternoon | exact
            $table->time('pickup_time')->nullable()->after('pickup_window');       // only for "exact"
            $table->string('pickup_schedule_note', 300)->nullable()->after('pickup_time');
            $table->timestamp('pickup_confirmed_at')->nullable()->after('pickup_schedule_note');
            $table->date('pickup_request_date')->nullable()->after('pickup_confirmed_at');
            $table->string('pickup_request_window', 12)->nullable()->after('pickup_request_date');
            $table->string('pickup_request_note', 300)->nullable()->after('pickup_request_window');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_date', 'pickup_window', 'pickup_time', 'pickup_schedule_note',
                'pickup_confirmed_at', 'pickup_request_date', 'pickup_request_window', 'pickup_request_note',
            ]);
        });
    }
};
