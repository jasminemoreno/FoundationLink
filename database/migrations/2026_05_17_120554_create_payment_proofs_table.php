<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monetary_donation_id')
                ->constrained('monetary_donations')
                ->onDelete('cascade');
            $table->string('file_path');           // path to receipt/screenshot
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable(); // image/jpeg, image/png, etc.
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};