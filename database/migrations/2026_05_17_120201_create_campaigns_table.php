<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foundation_id')
                ->constrained('foundations')
                ->onDelete('cascade');
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();
            $table->foreignId('created_by')
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover_photo')->nullable();
            $table->enum('type', ['monetary', 'item', 'both'])->default('both');
            $table->json('accepted_delivery_methods')->nullable();
            $table->json('accepted_payment_methods')->nullable();
            $table->decimal('goal_amount', 12, 2)->nullable();
            $table->decimal('current_amount', 12, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', [
                'active',
                'completed',
                'cancelled',
                'draft',
                'paused'
            ])->default('active');
            $table->text('pause_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};