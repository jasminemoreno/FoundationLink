<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_donation_id')
                ->constrained('item_donations')
                ->onDelete('cascade');
            $table->enum('method', ['drop_off', 'pickup']);
            $table->date('scheduled_date')->nullable();
            $table->string('scheduled_time')->nullable();
            $table->text('address')->nullable();        // drop-off location or pickup address
            $table->text('notes')->nullable();          // special instructions
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])
                ->default('scheduled');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};