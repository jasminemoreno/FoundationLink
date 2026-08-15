<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->onDelete('cascade');
            $table->foreignId('donor_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('item_name');
            $table->text('item_description')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('condition')->nullable(); // new, used, etc.
            $table->text('message')->nullable();
            $table->enum('delivery_method', ['drop_off', 'pickup'])->default('drop_off');
            $table->enum('status', ['pending', 'approved', 'rejected', 'received'])
                ->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_donations');
    }
};