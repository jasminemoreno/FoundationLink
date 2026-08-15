<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->onDelete('cascade');

            $table->foreignId('donor_id')
                ->constrained('users')
                ->onDelete('cascade');

            // type of donation
            $table->enum('type', ['monetary', 'item']);

            // for monetary donations
            $table->decimal('amount', 12, 2)->nullable();
            $table->foreignId('payment_method_id')
                ->nullable()
                ->constrained('payment_methods')
                ->nullOnDelete();
            $table->string('proof_photo')->nullable();

            // for item donations
            $table->string('item_name')->nullable();
            $table->integer('item_quantity')->nullable();
            $table->text('item_description')->nullable();
            $table->string('item_photo')->nullable();

            // delivery (for item donations only)
            $table->enum('delivery_method', ['pickup', 'dropoff'])->nullable();
            $table->text('delivery_address')->nullable();

            // status
            $table->enum('status', [
                'pending',
                'received',
                'cancelled'
            ])->default('pending');

            $table->text('notes')->nullable();
            $table->timestamp('donated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};