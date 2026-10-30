<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An optional personal message from the foundation, written when it marks a
 * donation as received. The donor sees it in the notification and in the
 * donation's details.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('thank_you_note', 500)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn('thank_you_note');
        });
    }
};
