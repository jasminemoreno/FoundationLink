<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('foundation_payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foundation_id')
                ->constrained('foundations')
                ->onDelete('cascade');
            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->onDelete('cascade');
            $table->string('account_name');    // e.g. "Juan Dela Cruz"
            $table->string('account_number'); // e.g. "09171234567"
            $table->timestamps();

            // One account per payment method per foundation
            $table->unique(['foundation_id', 'payment_method_id'], 'fpa_foundation_payment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundation_payment_accounts');
    }
};